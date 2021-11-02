<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Connection;
use Anik\Amqp\Consumable;
use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Consumer;
use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Exchanges\Topic;
use Anik\Amqp\Qos\Qos;
use Anik\Amqp\Queues\Queue;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumerTest extends AmqpTestCase
{
    protected function getConsumer(
        ?AbstractConnection $connection = null,
        ?AMQPChannel $channel = null,
        array $options = []
    ): Consumer {
        return new Consumer($connection ?? $this->connection, $channel ?? $this->channel, $options);
    }

    protected function queueDeclareExpectation($times = null, $return = null)
    {
        $this->setMethodExpectations($this->channel, 'queue_declare', $times, $return);
    }

    protected function queueBindExpectation($times = null, $return = null)
    {
        $this->setMethodExpectations($this->channel, 'queue_bind', $times, $return);
    }

    protected function consumerIsConsumingExpectation($times = null, $return = true)
    {
        $this->setMethodExpectations($this->channel, 'is_consuming', $times, $return);
    }

    protected function consumerWaitMethodExpectation($times = null, $return = null)
    {
        $this->setMethodExpectations($this->channel, 'wait', $times, $return);
    }

    protected function basicConsumeExpectation($times = null, $return = null)
    {
        $this->setMethodExpectations($this->channel, 'basic_consume', $times, $return);
    }

    protected function qosExpectation($times = null, $return = null)
    {
        $this->setMethodExpectations($this->channel, 'basic_qos', $times, $return);
    }

    protected function getConsumableInstance($assert = true): Consumable
    {
        return new ConsumableMessage(
            function (ConsumableMessage $message, AMQPMessage $original) use ($assert) {
                if ($assert) {
                    $this->assertInstanceOf(AMQPMessage::class, $original);
                    $this->assertInstanceOf(ConsumableMessage::class, $message);
                }
            }
        );
    }

    public function consumerCreateOptionsProvider(): array
    {
        return [
            'only sets tag' => [
                [
                    'options' => [
                        'tag' => 'consumer.tag',
                    ],
                    'expectations' => [
                        'tag' => 'consumer.tag',
                    ],
                ],
            ],
            'when all values are set' => [
                [
                    'options' => [
                        'tag' => 'consumer.tag',
                        'no_local' => true,
                        'no_ack' => true,
                        'exclusive' => true,
                        'no_wait' => true,
                        'arguments' => ['key' => 'value'],
                        'ticket' => 10,
                    ],
                    'expectations' => [
                        'tag' => 'consumer.tag',
                        'no_local' => true,
                        'no_ack' => true,
                        'exclusive' => true,
                        'no_wait' => true,
                        'arguments' => ['key' => 'value'],
                        'ticket' => 10,
                    ],
                ],
            ],
            'when no values are set' => [
                [
                    'expectations' => [
                        'no_local' => false,
                        'no_ack' => false,
                        'exclusive' => false,
                        'no_wait' => false,
                        'arguments' => [],
                        'ticket' => null,
                    ],
                ],
            ],
        ];
    }

    public function consumerConfigurationWhenConsumingQueueProvider(): array
    {
        return [
            'sets tag' => [
                [
                    'options' => [
                        'tag' => 'consumer.tag',
                    ],
                    'expectations' => [
                        'tag' => 'consumer.tag',
                    ],
                ],
            ],
            'when all values are set' => [
                [
                    'options' => [
                        'tag' => 'consumer.tag',
                        'no_local' => true,
                        'no_ack' => true,
                        'exclusive' => true,
                        'no_wait' => true,
                        'arguments' => ['key' => 'value'],
                        'ticket' => 10,
                    ],
                    'expectations' => [
                        'tag' => 'consumer.tag',
                        'no_local' => true,
                        'no_ack' => true,
                        'exclusive' => true,
                        'no_wait' => true,
                        'arguments' => ['key' => 'value'],
                        'ticket' => 10,
                    ],
                ],
            ],
            'when no values are set' => [
                [
                    'expectations' => [
                        'no_local' => false,
                        'no_ack' => false,
                        'exclusive' => false,
                        'no_wait' => false,
                        'arguments' => [],
                        'ticket' => null,
                    ],
                ],
            ],
        ];
    }

    public function exchangeDeclareDataProvider(): array
    {
        return [
            'when exchange is an instance and configuration is unavailable' => [
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
            'when exchange is null but option has configuration' => [
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
            'when exchange and configuration options both are available' => [
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
            'when exchange and configuration both are empty' => [
                [
                    'expectations' => [
                        'exception' => true,
                    ],
                ],
            ],
        ];
    }

    public function queueDeclareDataProvider(): array
    {
        return [
            'when queue is an instance and configuration is unavailable' => [
                [
                    'queue' => Queue::make(
                        [
                            'name' => self::QUEUE_NAME,
                            'declare' => true,
                            'passive' => true,
                            'exclusive' => true,
                            'arguments' => ['key' => 'value'],
                            'ticket' => 1,
                            'no_wait' => true,
                        ]
                    ),
                    'expectations' => [
                        'name' => self::QUEUE_NAME,
                        'declare' => true,
                        'passive' => true,
                        'exclusive' => true,
                        'arguments' => ['key' => 'value'],
                        'ticket' => 1,
                        'no_wait' => true,
                    ],
                ],
            ],
            'when queue is null but option has configuration' => [
                [
                    'options' => [
                        'name' => self::QUEUE_NAME,
                        'declare' => true,
                    ],
                    'expectations' => [
                        'name' => self::QUEUE_NAME,
                    ],
                ],
            ],
            'when queue and configuration options both are available' => [
                [
                    'queue' => Queue::make(['name' => self::QUEUE_NAME, 'declare' => true, 'durable' => false]),
                    'options' => [
                        'arguments' => ['key' => 'value'],
                        'ticket' => 12,
                        'no_wait' => true,
                    ],
                    'expectations' => [
                        'name' => self::QUEUE_NAME,
                        'ticket' => 12,
                        'arguments' => ['key' => 'value'],
                        'durable' => false,
                        'no_wait' => true,
                    ],
                ],
            ],
            'when queue and configuration both are empty' => [
                [
                    'expectations' => [
                        'exception' => true,
                    ],
                ],
            ],
        ];
    }

    public function qosDataProvider(): array
    {
        return [
            'when qos is passed as an instance' => [
                [
                    'qos' => Qos::make(
                        [
                            'prefetch_size' => 0,
                            'prefetch_count' => 0,
                            'global' => false,
                        ]
                    ),
                    'expectations' => [
                        'times' => $this->once(),
                        'prefetch_size' => 0,
                        'prefetch_count' => 0,
                        'global' => false,
                    ],
                ],
            ],
            'when qos is null but option has configuration' => [
                [
                    'options' => [
                        'prefetch_count' => 1,
                        'prefetch_size' => 2,
                        'global' => true,
                    ],
                    'expectations' => [
                        'times' => $this->once(),
                        'prefetch_count' => 1,
                        'prefetch_size' => 2,
                        'global' => true,
                    ],
                ],
            ],
            'when qos and configuration options both are available' => [
                [
                    'qos' => Qos::make(
                        [
                            'prefetch_size' => 5,
                            'prefetch_count' => 10,
                            'global' => false,
                        ]
                    ),
                    'options' => [
                        'prefetch_count' => 1,
                        'prefetch_size' => 2,
                        'global' => true,
                    ],
                    'expectations' => [
                        'times' => $this->once(),
                        'prefetch_count' => 1,
                        'prefetch_size' => 2,
                        'global' => true,
                    ],
                ],
            ],
            'when qos and configuration both are empty' => [[],],
        ];
    }

    public function queueBindDataProvider(): array
    {
        return [
            'with no options passed' => [
                [
                    'expectations' => [
                        'queue_name' => self::QUEUE_NAME,
                        'exchange_name' => self::EXCHANGE_NAME,
                    ],
                ],
            ],
            'with arguments passed' => [
                [
                    'options' => [
                        'arguments' => ['key' => 'value'],
                    ],
                    'expectations' => [
                        'queue_name' => self::QUEUE_NAME,
                        'exchange_name' => self::EXCHANGE_NAME,
                        'arguments' => ['key' => 'value'],
                    ],
                ],
            ],
            'for default exchange should not bind to queue' => [
                [
                    'exchange' => Exchange::make(['name' => '', 'type' => Exchange::TYPE_DIRECT, 'declare' => false]),
                    'expectations' => [
                        'times' => $this->never(),
                    ],
                ],
            ],
            'with binding key' => [
                [
                    'binding_key' => 'my.binding.key',
                    'expectations' => [
                        'queue_name' => self::QUEUE_NAME,
                        'exchange_name' => self::EXCHANGE_NAME,
                        'binding_key' => 'my.binding.key',
                    ],
                ],
            ],
        ];
    }

    public function consumerWaitMethodParamProvider(): array
    {
        return [
            'when no parameters are passed' => [[]],
            'when all values are passed' => [
                [
                    'allowed_methods' => ['60,60'],
                    'non_blocking' => true,
                    'timeout' => 10,
                ],
            ],
            'when only allowed methods are passed' => [
                [
                    'allowed_methods' => ['60,60'],
                ],
            ],
        ];
    }

    public function testConsumerIsAChildOfConnection()
    {
        $this->assertInstanceOf(Connection::class, $this->getConsumer());
    }

    /**
     * @dataProvider consumerCreateOptionsProvider
     *
     * @param array $data
     */
    public function testConsumerInstantiationConfiguresCorrectly(array $data)
    {
        $consumer = $this->getConsumer(null, null, $data['options'] ?? []);

        if ($name = ($data['expectations']['tag'] ?? '')) {
            $this->assertSame($consumer->getConsumerTag(), $name);
        }
        $this->assertSame($consumer->isNoLocal(), $data['expectations']['no_local'] ?? false);
        $this->assertSame($consumer->isNoAck(), $data['expectations']['no_ack'] ?? false);
        $this->assertSame($consumer->isExclusive(), $data['expectations']['exclusive'] ?? false);
        $this->assertSame($consumer->isNowait(), $data['expectations']['no_wait'] ?? false);
        $this->assertSame($consumer->getArguments(), $data['expectations']['arguments'] ?? []);
        $this->assertSame($consumer->getTicket(), $data['expectations']['ticket'] ?? null);
    }

    /**
     * @dataProvider consumerConfigurationWhenConsumingQueueProvider
     *
     * @param array $data
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testConsumerIsReconfiguredDuringTheConsumeMethod(array $data)
    {
        $this->exchangeDeclareExpectation($this->never());
        $this->queueDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        $this->qosExpectation($this->never());
        // Don't consume any message
        $this->consumerIsConsumingExpectation($this->once(), false);
        $this->consumerWaitMethodExpectation($this->never());

        $consumer = $this->getConsumer();

        $options = ($data['options'] ?? false) ? ['consumer' => $data['options']] : [];

        $consumer->consume(
            $this->getConsumableInstance(false),
            $this->getBindingKey(),
            Topic::make(['name' => self::EXCHANGE_NAME, 'declare' => false]),
            Queue::make(['name' => self::QUEUE_NAME, 'declare' => false]),
            null,
            $options
        );

        if ($name = ($data['expectations']['tag'] ?? '')) {
            $this->assertSame($consumer->getConsumerTag(), $name);
        }

        $this->assertSame($consumer->isNoLocal(), $data['expectations']['no_local'] ?? false);
        $this->assertSame($consumer->isNoAck(), $data['expectations']['no_ack'] ?? false);
        $this->assertSame($consumer->isExclusive(), $data['expectations']['exclusive'] ?? false);
        $this->assertSame($consumer->isNowait(), $data['expectations']['no_wait'] ?? false);
        $this->assertSame($consumer->getArguments(), $data['expectations']['arguments'] ?? []);
        $this->assertSame($consumer->getTicket(), $data['expectations']['ticket'] ?? null);
    }

    /**
     * @dataProvider exchangeDeclareDataProvider
     *
     * @param array $data
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testConsumerConfiguresExchangeCorrectly(array $data)
    {
        $bindingKey = $this->getBindingKey();
        $exchange = $data['exchange'] ?? null;
        $queue = Queue::make(['name' => self::QUEUE_NAME, 'declare' => false]);
        $options = $data['options'] ?? [];

        if ($data['expectations']['exception'] ?? false) {
            $this->expectException(AmqpException::class);
        } else {
            $this->queueDeclareExpectation($this->never());
            $this->queueBindExpectation($this->once());
            $this->qosExpectation($this->never());
            // Don't consume any message
            $this->consumerIsConsumingExpectation($this->once(), false);
            $this->consumerWaitMethodExpectation($this->never());

            $expectedName = $data['expectations']['name'];
            $expectedType = $data['expectations']['type'];
            $expectedPassive = $data['expectations']['passive'] ?? false;
            $expectedDurable = $data['expectations']['durable'] ?? true;
            $expectedAutoDelete = $data['expectations']['auto_delete'] ?? false;
            $expectedInternal = $data['expectations']['internal'] ?? false;
            $expectedNowait = $data['expectations']['no_wait'] ?? false;
            $expectedArguments = $data['expectations']['arguments'] ?? [];
            $expectedTicket = $data['expectations']['ticket'] ?? null;
            $this->exchangeDeclareExpectation(
                $this->once(),
                $this->returnCallback(
                    function (
                        $name,
                        $type,
                        $passive,
                        $durable,
                        $autoDelete,
                        $internal,
                        $nowait,
                        $arguments,
                        $ticket
                    ) use (
                        $expectedName,
                        $expectedType,
                        $expectedPassive,
                        $expectedDurable,
                        $expectedAutoDelete,
                        $expectedInternal,
                        $expectedNowait,
                        $expectedArguments,
                        $expectedTicket
                    ) {
                        $this->assertSame($expectedName, $name);
                        $this->assertSame($expectedType, $type);
                        $this->assertSame($expectedPassive, $passive);
                        $this->assertSame($expectedDurable, $durable);
                        $this->assertSame($expectedAutoDelete, $autoDelete);
                        $this->assertSame($expectedInternal, $internal);
                        $this->assertSame($expectedNowait, $nowait);
                        $this->assertSame($expectedArguments, $arguments);
                        $this->assertSame($expectedTicket, $ticket);
                    }
                )
            );
        }

        $this->getConsumer()->consume(
            $this->getConsumableInstance(false),
            $bindingKey,
            $exchange,
            $queue,
            null,
            ['exchange' => $options]
        );
    }

    /**
     * @dataProvider queueDeclareDataProvider
     *
     * @param array $data
     */
    public function testConsumerConfiguresQueueCorrectly(array $data)
    {
        $exchange = Topic::make(['name' => self::EXCHANGE_NAME, 'declare' => false]);
        $queue = $data['queue'] ?? null;
        $options = $data['options'] ?? [];

        if ($data['expectations']['exception'] ?? false) {
            $this->expectException(AmqpException::class);
        } else {
            $this->exchangeDeclareExpectation($this->never());
            $this->queueBindExpectation($this->once());
            $this->qosExpectation($this->never());
            // Don't consume any message
            $this->consumerIsConsumingExpectation($this->once(), false);
            $this->consumerWaitMethodExpectation($this->never());

            $expectedName = $data['expectations']['name'];
            $expectedPassive = $data['expectations']['passive'] ?? false;
            $expectedDurable = $data['expectations']['durable'] ?? true;
            $expectedExclusive = $data['expectations']['exclusive'] ?? false;
            $expectedAutoDelete = $data['expectations']['auto_delete'] ?? false;
            $expectedNowait = $data['expectations']['no_wait'] ?? false;
            $expectedArguments = $data['expectations']['arguments'] ?? [];
            $expectedTicket = $data['expectations']['ticket'] ?? null;
            $this->channel->expects($this->once())->method('queue_declare')->with(
                $expectedName,
                $expectedPassive,
                $expectedDurable,
                $expectedExclusive,
                $expectedAutoDelete,
                $expectedNowait,
                $expectedArguments,
                $expectedTicket
            )->willReturn([$expectedName]);
        }

        $this->getConsumer()->consume(
            $this->getConsumableInstance(false),
            $this->getBindingKey(),
            $exchange,
            $queue,
            null,
            ['queue' => $options]
        );
    }

    /**
     * @depends  testConsumerConfiguresQueueCorrectly
     */
    public function testQueueDeclareResetsQueueNameIfMismatch()
    {
        $exchange = Topic::make(['name' => self::EXCHANGE_NAME, 'declare' => false]);
        $queue = Queue::make(['name' => '', 'declare' => true,]);
        $this->exchangeDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        $this->qosExpectation($this->never());
        // Don't consume any message
        $this->consumerIsConsumingExpectation($this->once(), false);
        $this->consumerWaitMethodExpectation($this->never());

        $this->channel->expects($this->once())->method('queue_declare')->willReturn([self::QUEUE_NAME]);

        $this->getConsumer()->consume(
            $this->getConsumableInstance(false),
            $this->getBindingKey(),
            $exchange,
            $queue
        );
        $this->assertSame(self::QUEUE_NAME, $queue->getName());
    }

    /**
     * @dataProvider qosDataProvider
     *
     * @param array $data
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testConsumerConfiguresQosCorrectly(array $data)
    {
        $exchange = Topic::make(['name' => self::EXCHANGE_NAME, 'declare' => false]);
        $queue = Queue::make(['name' => self::QUEUE_NAME, 'declare' => false]);
        $qos = $data['qos'] ?? null;
        $options = $data['options'] ?? [];

        $this->exchangeDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        // Don't consume any message
        $this->consumerIsConsumingExpectation($this->once(), false);
        $this->consumerWaitMethodExpectation($this->never());

        $invocation = $this->channel->expects($data['expectations']['times'] ?? $this->never())
                                    ->method('basic_qos')
                                    ->willReturn(null);
        if ($data['expectations']['prefetch_size'] ?? false) {
            $invocation->with(
                $data['expectations']['prefetch_size'] ?? 0,
                $data['expectations']['prefetch_count'] ?? 0,
                $data['expectations']['global'] ?? false
            );
        }

        $this->getConsumer()->consume(
            $this->getConsumableInstance(false),
            $this->getBindingKey(),
            $exchange,
            $queue,
            $qos,
            $options ? ['qos' => $options] : []
        );
    }

    /**
     * @dataProvider queueBindDataProvider
     *
     * @param array $data
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testConsumerBindsQueueCorrectly(array $data)
    {
        $exchange = $data['exchange'] ?? Topic::make(['name' => self::EXCHANGE_NAME, 'declare' => false]);
        $queue = $data['queue'] ?? Queue::make(['name' => self::QUEUE_NAME, 'declare' => false]);
        $qos = $data['qos'] ?? null;
        $options = $data['options'] ?? [];

        $queueName = $data['expectations']['queue_name'] ?? ''; // when queue should never bind
        $exchangeName = $data['expectations']['exchange_name'] ?? ''; // when queue should never bind
        $bindingKey = $data['expectations']['binding_key'] ?? ''; //when queue should never bind
        $nowait = $data['expectations']['no_wait'] ?? false;
        $arguments = $data['expectations']['arguments'] ?? [];
        $ticket = $data['expectations']['ticket'] ?? null;

        $this->exchangeDeclareExpectation($this->never());
        $this->queueDeclareExpectation($this->never());
        $this->channel->expects($data['expectations']['times'] ?? $this->once())
                      ->method('queue_bind')
                      ->with($queueName, $exchangeName, $bindingKey, $nowait, $arguments, $ticket)
                      ->willReturn(null);
        // Don't consume any message
        $this->consumerIsConsumingExpectation($this->once(), false);
        $this->consumerWaitMethodExpectation($this->never());

        $invocation = $this->channel->expects($data['expectations']['times'] ?? $this->never())
                                    ->method('basic_qos')
                                    ->willReturn(null);
        if ($data['expectations']['prefetch_size'] ?? false) {
            $invocation->with(
                $data['expectations']['prefetch_size'] ?? 0,
                $data['expectations']['prefetch_count'] ?? 0,
                $data['expectations']['global'] ?? false
            );
        }

        $this->getConsumer()->consume(
            $this->getConsumableInstance(false),
            $data['binding_key'] ?? $this->getBindingKey(''),
            $exchange,
            $queue,
            $qos,
            $options ? ['bind' => $options] : []
        );
    }

    /**
     * @dataProvider consumerWaitMethodParamProvider
     *
     * @param array $data
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testConsumerCallsWaitMethodOnChannelWhenWaitingForMessageWithAppropriateData(array $data)
    {
        $exchange = Topic::make(['name' => self::EXCHANGE_NAME, 'declare' => false]);
        $queue = Queue::make(['name' => self::QUEUE_NAME, 'declare' => false]);
        $options = $data['options'] ?? [];

        $allowedMethods = $options['allowed_methods'] ?? null;
        $nonBlocking = $options['non_blocking'] ?? false;
        $timeout = $options['timeout'] ?? 0;
        $this->exchangeDeclareExpectation($this->never());
        $this->queueDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        $this->qosExpectation($this->never());
        $this->channel->expects($this->exactly(2))->method('is_consuming')->willReturnOnConsecutiveCalls([true, false]);
        $this->channel->expects($this->exactly(1))
                      ->method('wait')
                      ->with($allowedMethods, $nonBlocking, $timeout)
                      ->willReturn(null);

        $this->getConsumer()->consume(
            $this->getConsumableInstance(false),
            $this->getBindingKey(),
            $exchange,
            $queue,
            null,
            $options ? ['consume' => $options] : []
        );
    }
}
