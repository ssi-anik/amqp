<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Exchanges\Topic;
use Anik\Amqp\Queues\Queue;
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
        $instance->expects($this->timesToInvocation($times))->method($method)->will(
            $return instanceof Stub ? $return : $this->returnValue($return)
        );

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

    protected function timesToInvocation($times = null): InvocationOrder
    {
        return $times instanceof InvocationOrder ? $times : (is_null($times) ? $this->any() : $this->exactly($times));
    }

    protected function convertReturnToStub($return = null): Stub
    {
        return $return instanceof Stub ? $return : $this->returnValue($return);
    }

    protected function exchangeDeclareExpectation($times = null, $return = null)
    {
        $this->channel->expects($this->timesToInvocation($times))->method('exchange_declare')->will(
            $this->convertReturnToStub($return)
        );
    }

    protected function exchangeOptions(?array $options = null): array
    {
        return ($options ?? []) + [
                'name' => self::EXCHANGE_NAME,
                'type' => Exchange::TYPE_DIRECT,
                'declare' => false,
                'passive' => false,
                'durable' => true,
                'auto_delete' => false,
                'internal' => false,
                'no_wait' => false,
                'arguments' => [],
                'ticket' => null,
            ];
    }

    protected function queueOptions(?array $options = null): array
    {
        return ($options ?? []) + [
                'name' => self::QUEUE_NAME,
                'declare' => false,
                'passive' => false,
                'durable' => true,
                'exclusive' => false,
                'auto_delete' => false,
                'no_wait' => false,
                'arguments' => [],
                'ticket' => null,
            ];
    }

    protected function getExchange(?array $options = null): Exchange
    {
        return Exchange::make($this->exchangeOptions($options));
    }

    protected function getQueue(?array $options = null): Queue
    {
        return Queue::make($this->queueOptions($options));
    }

    protected function getRoutingKey(?string $key = null): string
    {
        return $key ?? self::ROUTING_KEY;
    }

    protected function getBindingKey(?string $key = null): string
    {
        return $key ?? self::BINDING_KEY;
    }

    public function exchangeDeclareDataProvider(): array
    {
        return [
            'when exchange is an instance and configuration is empty' => [
                [
                    'exchange' => Exchange::make(
                        [
                            'name' => self::EXCHANGE_NAME,
                            'type' => Exchange::TYPE_DIRECT,
                            'declare' => true,
                            'passive' => true,
                            'arguments' => ['key' => 'value'],
                            'ticket' => 1,
                            'no_wait' => true,
                        ]
                    ),
                    'expectations' => [
                        'name' => self::EXCHANGE_NAME,
                        'type' => Exchange::TYPE_DIRECT,
                        'declare' => true,
                        'passive' => true,
                        'arguments' => ['key' => 'value'],
                        'ticket' => 1,
                        'no_wait' => true,
                    ],
                ],
            ],
            'when exchange is null and configuration is non-empty' => [
                [
                    'options' => [
                        'name' => self::EXCHANGE_NAME,
                        'type' => Exchange::TYPE_HEADERS,
                        'declare' => true,
                    ],
                    'expectations' => [
                        'name' => self::EXCHANGE_NAME,
                        'type' => Exchange::TYPE_HEADERS,
                    ],
                ],
            ],
            'when exchange is an instance and configuration is non-empty' => [
                [
                    'exchange' => Topic::make(['name' => self::EXCHANGE_NAME, 'declare' => true, 'durable' => false]),
                    'options' => [
                        'arguments' => ['key' => 'value'],
                        'ticket' => 12,
                        'no_wait' => true,
                    ],
                    'expectations' => [
                        'name' => self::EXCHANGE_NAME,
                        'type' => Exchange::TYPE_TOPIC,
                        'ticket' => 12,
                        'arguments' => ['key' => 'value'],
                        'durable' => false,
                        'no_wait' => true,
                    ],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getConnectionMock();
        $this->channel = $this->getChannelMock();
    }
}
