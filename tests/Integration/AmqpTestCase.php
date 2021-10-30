<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Exchanges\Exchange;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\MockObject\Stub\Stub;
use PHPUnit\Framework\TestCase;

class AmqpTestCase extends TestCase
{
    protected $connection;
    protected $channel;

    public const ROUTING_KEY = 'anik.amqp.routing_key';
    public const BINDING_KEY = 'anik.amqp.routing_key';
    public const EXCHANGE_NAME = 'anik.amqp.exchange';
    public const QUEUE_NAME = 'anik.amqp.queue';

    protected function getConnectionMock($class = AMQPLazySSLConnection::class): AbstractConnection
    {
        if (!is_subclass_of($class, AbstractConnection::class)) {
            throw new \Exception('Must be an implementation of PhpAmqpLib\Connection\AbstractConnection::class');
        }

        return $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
    }

    protected function getChannelMock($channelId = 1, $getChannelIdMethodCount = null): AMQPChannel
    {
        $channel = $this->getAmqpChannelMock();

        if (is_null($getChannelIdMethodCount)) {
            $count = $this->any();
        } elseif (0 === $getChannelIdMethodCount) {
            $count = $this->never();
        } else {
            $count = $this->exactly($getChannelIdMethodCount);
        }

        $channel->expects($count)->method('getChannelId')->willReturn($channelId);

        return $channel;
    }

    protected function getAmqpChannelMock(): AMQPChannel
    {
        return $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();
    }

    protected function setMethodExpectations(MockObject $instance, $method, $times, $return): MockObject
    {
        if (!$times instanceof InvocationOrder) {
            $times = is_null($times) ? $this->any() : $this->exactly($times);
        }

        $invocation = $instance->expects($times)->method($method);
        if ($return instanceof Stub) {
            $invocation->will($return);
        } else {
            $invocation->willReturn($return);
        }

        return $instance;
    }

    protected function setMethodExpectationsOnConnection(array $options): void
    {
        $mapper = [
            'connect' => 'connectOnConstruct',
            'connectOnConstruct' => 'connectOnConstruct',
        ];

        foreach ($options as $method => $params) {
            $method = $mapper[$method] ?? $method;
            $return = $params['params'] ?? $params['parameters'] ?? $params['return'] ?? $params;
            $this->setMethodExpectations($this->connection, $method, $params['times'] ?? null, $return);
        }
    }

    protected function setMethodExpectationsOnChannel(array $options): void
    {
        $mapper = [];

        foreach ($options as $method => $params) {
            $method = $mapper[$method] ?? $method;
            $return = $params['checks'] ?? $params['return'] ?? $params['params'] ?? $params['parameters'] ?? $params;
            $this->setMethodExpectations($this->channel, $method, $params['times'] ?? null, $return);
        }
    }

    protected function exchangeDeclareExpectation($times = null, $return = null)
    {
        $this->setMethodExpectations($this->channel, 'exchange_declare', $times, $return);
    }

    protected function exchangeOptions(?array $options = null): array
    {
        return ($options ?? []) + [
                'name' => self::EXCHANGE_NAME,
                'type' => Exchange::TYPE_DIRECT,
                'declare' => true,
                'passive' => true,
                'durable' => true,
                'auto_delete' => false,
                'internal' => false,
                'no_wait' => false,
                'arguments' => [],
                'ticket' => null,
            ];
    }

    protected function getExchange(?array $options = null): Exchange
    {
        return Exchange::make($this->exchangeOptions($options));
    }

    protected function routingKey(?string $key = null): string
    {
        return $key ?? self::ROUTING_KEY;
    }

    protected function bindingKey(?string $key = null): string
    {
        return $key ?? self::BINDING_KEY;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getConnectionMock();
        $this->channel = $this->getChannelMock();
    }
}
