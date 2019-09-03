<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use Illuminate\Container\Container;
use InvalidArgumentException;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;

class AmqpManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    private $app;

    /**
     * AmqpConnection instance
     * @var \PhpAmqpLib\Connection\AMQPConnection[]
     */
    private $connections;

    /**
     * AmqpChannel instance
     * @var \PhpAmqpLib\Channel\AMQPChannel[]
     */
    private $channels;

    public function __construct (Container $app) {
        $this->app = $app;
    }

    protected function getConfig ($connection) {
        return $this->app['config']["amqp.connections.{$connection}"];
    }

    public function getDefaultConnection () {
        return $this->app['config']['amqp.default'];
    }

    public function setDefaultConnection ($name) {
        $this->app['config']['amqp.default'] = $name;
    }

    protected function getConnection ($name) {
        return $this->connections[$name] ?? ($this->connections[$name] = $this->resolve($name));
    }

    protected function resolveConnectionName ($name) {
        return $name ?: $this->getDefaultConnection();
    }

    protected function guessChannelId ($channelId, $connection) {
        return is_null($channelId) ? $this->getConfig($connection)['channel_id'] ?? null : $channelId;
    }

    protected function resolve ($name) {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Amqp connection [{$name}] is not defined.");
        }

        if (!isset($config['connection'])) {
            throw new AMQPException("[{$name}] connection properties are not defined.");
        }

        return $this->connect($config['connection']);
    }

    protected function connect (array $config) : AbstractConnection {
        return new AMQPSSLConnection($config['host'], $config['port'], $config['username'], $config['password'], $config['vhost'], $config['ssl_options'] ?? [], $config['connect_options'] ?? [], $config['ssl_protocol'] ?? 'ssl');
    }

    protected function acquireChannel ($name, AbstractConnection $connection, $channelId) {
        return $this->channels[$name][$channelId] ?? ($this->channels[$name][$channelId] = $connection->channel($channelId));
    }

    /**
     * @param string|\Anik\Amqp\PublishableMessage $message
     * @param array                                $default
     * @param array                                $dynamic
     *
     * @return \Anik\Amqp\PublishableMessage
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    protected function constructMessage ($message, array $default = [], array $dynamic = []) : PublishableMessage {
        $publishable = $message;
        $properties = [];
        if (is_string($message)) {
            $publishable = new PublishableMessage($message);
        } elseif ($message instanceof PublishableMessage) {
            $properties = array_merge($default, $message->getProperties(), $dynamic);
        } else {
            throw new AmqpException('Message can be typeof string or Anik\Amqp\PublishableMessage.');
        }

        if (count($properties)) {
            $publishable->setProperties($properties);
        }

        return $publishable;
    }

    /**
     * @param $closure
     *
     * @return \Anik\Amqp\ConsumableMessage
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    protected function constructReceivableMessage ($closure) : ConsumableMessage {
        if (is_callable($closure)) {
            return new GenericConsumableMessage($closure);
        } elseif ($closure instanceof ConsumableMessage) {
            return $closure;
        } else {
            throw new AmqpException('Handler can be typeof Closure or Anik\Amqp\ConsumableMessage');
        }
    }

    public function publish ($message, $routingKey, array $config = []) {
        /**
         * Connection and channels are not closed on purpose
         * - Check here "Don’t open and close connections or channels repeatedly" - https://www.cloudamqp.com/blog/2018-01-19-part4-rabbitmq-13-common-errors.html
         * - The connections are closed on destruction by default (w/ channels): https://github.com/php-amqplib/php-amqplib/blob/v2.9.2/PhpAmqpLib/Connection/AbstractConnection.php#L289
         * Close on destruct is default set to `true`
         */
        $name = $this->resolveConnectionName($config['connection'] ?? null);
        $connection = $this->getConnection($name);
        $channelId = $this->guessChannelId($config['channel_id'] ?? null, $name);
        $channel = $this->acquireChannel($name, $connection, $channelId);

        /* No exception raised, configuration available */
        $defaultConfig = $this->getConfig($name);

        $messageDefault = $defaultConfig['message'] ?? [];

        $passableMessages = [];
        foreach ( (!is_array($message) ? [ $message ] : $message) as $msg ) {
            $pMsg = $this->constructMessage($msg, $messageDefault, $config['message'] ?? []);

            /* Merge exchange properties */
            $classExProp = [];
            if ($pMsg->getExchange()) {
                // if an exchange is passed, name is possibly not there in properties. If present, it'll be overwritten
                $classExProp = array_merge($pMsg->getExchange()->getProperties(), [
                    'name' => $pMsg->getExchange()->getName(),
                ]);
            }
            // dynamic value > class value > default values
            // Merge the properties array first
            $exchangeProperties = array_merge($defaultConfig['exchange']['properties'] ?? [], $classExProp['properties'] ?? [], $config['exchange']['properties'] ?? []);
            // append the properties/arguments at the end so that it overwrites the default or any other values
            $exchangeConfig = array_merge($defaultConfig['exchange'] ?? [], $classExProp, $config['exchange'] ?? [], [
                'properties' => $exchangeProperties,
            ]);
            $pMsg->setExchange(new Exchange($exchangeConfig['name'] ?? '', $exchangeConfig));

            $passableMessages[] = $pMsg;
        }

        /* @var \Anik\Amqp\Publisher $publisher */
        $publisher = app(Publisher::class);
        $publisher->setChannel($channel)->publishBulk($passableMessages, $routingKey);
    }

    public function consume ($closure, $bindingKey, array $config = []) {
        $name = $this->resolveConnectionName($config['connection'] ?? null);
        $connection = $this->getConnection($name);
        $channelId = $this->guessChannelId($config['channel_id'] ?? null, $name);
        $channel = $this->acquireChannel($name, $connection, $channelId);

        /* No exception raised, configuration available */
        $defaultConfig = $this->getConfig($name);

        $handler = $this->constructReceivableMessage($closure);

        $exClassProp = [];
        if ($ex = $handler->getExchange()) {
            // if an exchange properties has name key that name will be overwritten by the exchange class's name
            $exClassProp = array_merge($ex->getProperties(), [
                'name' => $ex->getName(),
            ]);
        }
        /* dynamic value > class value > default values */
        $mergedExchangeConfig = array_merge($defaultConfig['exchange'] ?? [], $exClassProp, $config['exchange'] ?? []);
        $handler->setExchange(new Exchange($mergedExchangeConfig['name'] ?? '', $mergedExchangeConfig));

        $qClassProp = [];
        if ($q = $handler->getQueue()) {
            // if a queue properties has name key that name will be overwritten by the queue class's name
            $qClassProp = array_merge($q->getProperties(), [
                'name' => $q->getName(),
            ]);
        }
        /* dynamic value > class value > default values */
        /* Merge b_property & d_property separately before */
        $b_properties = array_merge($defaultConfig['queue']['b_properties'] ?? [], $qClassProp['b_properties'] ?? [], $config['queue']['b_properties'] ?? []);
        $d_properties = array_merge($defaultConfig['queue']['d_properties'] ?? [], $qClassProp['d_properties'] ?? [], $config['queue']['d_properties'] ?? []);
        /* Now merge the bind and declare properties with the existing values */
        $mergedQueueConfig = array_merge($defaultConfig['queue'] ?? [], $qClassProp, $config['queue'] ?? [], [
            'd_properties' => $d_properties,
            'b_properties' => $b_properties,
        ]);
        $handler->setQueue(new Queue($mergedQueueConfig['name'] ?? '', $mergedQueueConfig));

        $conClassProp = [];
        if ($cons = $handler->getConsumer()) {
            $conClassProp = $cons->getProperties();
        }
        /* dynamic value > class value > default values */
        $mergedConsumerConfig = array_merge($defaultConfig['consumer'] ?? [], $conClassProp, $config['consumer'] ?? []);
        $handler->setConsumer(new AmqpConsumer($mergedConsumerConfig));

        /* QoS is not attached to any exchange, queue */
        $mergedQosConfig = array_merge($defaultConfig['qos'] ?? [], $config['qos'] ?? []);
        if (isset($mergedQosConfig['enabled']) && $mergedQosConfig['enabled']) {
            $channel->basic_qos($mergedQosConfig['qos_prefetch_size'], $mergedQosConfig['qos_prefetch_count'], $mergedQosConfig['qos_a_global']);
        }

        /* @var \Anik\Amqp\Consumer $consumer */
        $consumer = app(Consumer::class);
        $consumer->setChannel($channel)->consume($handler, $bindingKey);
    }
}
