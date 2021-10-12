<?php

namespace Anik\Amqp;

use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchange;
use Anik\Amqp\GenericConsumableMessage;
use Anik\Amqp\PublishableMessage;
use Carbon\Carbon;
use Illuminate\Container\Container;
use PHPUnit\Framework\Assert as PHPUnit;
use SplQueue;

class AmqpManagerFake {
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    private $app;

    private static $publishedMessages;
    private static $consumedMessages;

    private static $messagesToConsume;

    private $cancelledConsume;

    public static function __constructStatic() {
        self::$messagesToConsume = new SplQueue();
        self::$publishedMessages = [];
        self::$consumedMessages = [];
    }

    public function __construct(Container $app) {
        $this->app = $app;

    }

    public static function register() {
        self::$messagesToConsume = new SplQueue();
        self::$publishedMessages = [];
        self::$consumedMessages = [];
        app()->extend('amqp', function ($command, $app) {
            return new AmqpManagerFake($app);
        });
    }

    protected function getConfig($connection) {
        return $this->app['config']["amqp.connections.{$connection}"];
    }

    public function getDefaultConnection() {
        return $this->app['config']['amqp.default'];
    }

    public function setDefaultConnection($name) {
        $this->app['config']['amqp.default'] = $name;
    }

    protected function resolveConnectionName($name) {
        return $name ?: $this->getDefaultConnection();
    }

    protected function guessChannelId($channelId, $connection) {
        return is_null($channelId) ? $this->getConfig($connection)['channel_id'] ?? null : $channelId;
    }

    /**
     * @param string|\Anik\Amqp\PublishableMessage $message
     * @param array $default
     * @param array $dynamic
     *
     * @return \Anik\Amqp\PublishableMessage
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    protected function constructMessage($message, array $default = [], array $dynamic = []): PublishableMessage {
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
    protected function constructReceivableMessage($closure): ConsumableMessage {
        if (is_callable($closure)) {
            return new GenericConsumableMessage($closure);
        } elseif ($closure instanceof ConsumableMessage) {
            return $closure;
        } else {
            throw new AmqpException('Handler can be typeof Closure or Anik\Amqp\ConsumableMessage');
        }
    }

    public function publish($message, $routingKey, array $config = []) {
        $messageDefault = $defaultConfig['message'] ?? [];
        foreach ((!is_array($message) ? [$message] : $message) as $msg) {
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

            self::$publishedMessages[] = $pMsg;
        }
    }

    public function consume($closure, $bindingKey, array $config = [], ?int $seconds = null) {
        $until = $seconds === null || $seconds < 0 ? null : Carbon::now()->addSeconds($seconds);
        $this->cancelledConsume = false;
        while (!$this->cancelledConsume && ($until == null || $until < now())) {
            /** @var ConsumableMessage|null $message */
            $message = self::$messagesToConsume->dequeue();
            if ($message != null) {
                self::$consumedMessages[] = $message;
                $closure($message);
            } else {
                sleep(1);
            }
        }
    }

    public function pushToConsumer(ConsumableMessage $message) {
        self::$messagesToConsume->enqueue($message);
    }

    public function cancelConsume() {
        $this->cancelledConsume = true;
    }

    public static function assertPublishedCount($count) {
        PHPUnit::assertCount(
            $count,
            self::$publishedMessages
        );
    }

    public static function assertPublished() {
        PHPUnit::assertNotEmpty(
            self::$publishedMessages,
            'Failed asserting that something was published.'
        );
    }

    public static function assertNothingPublished() {
        PHPUnit::assertEmpty(
            self::$publishedMessages,
            'Failed asserting that nothing was published.'
        );
    }

    public static function assertConsumedCount($count) {
        PHPUnit::assertCount(
            $count,
            self::$consumedMessages,
            'Failed asserting that ' . $count . ' messages have been consumed.'
        );
    }

    public static function assertConsumed() {
        PHPUnit::assertNotEmpty(
            self::$consumedMessages,
            'Failed asserting that something was consumed.'
        );
    }

    public static function assertNothingConsumed() {
        PHPUnit::assertEmpty(
            self::$consumedMessages,
            'Failed asserting that nothing was consumed.'
        );
    }
}
