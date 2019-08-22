<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use Illuminate\Container\Container;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
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

    private function connect (array $config) : AbstractConnection {
        return new AMQPSSLConnection($config['host'], $config['port'], $config['username'], $config['password'],
            $config['vhost'], $config['ssl_options'] ?? [], $config['connect_options'] ?? [],
            $config['ssl_protocol'] ?? 'ssl');
    }

    protected function acquireChannel ($name, AbstractConnection $connection, $channelId) {
        return $this->channels[$name][$channelId] ?? ($this->channels[$name][$channelId] = $connection->channel($channelId));
    }

    public function publish ($message, array $config = []) {
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
    }
}
