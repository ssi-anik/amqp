<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Connection;
use Anik\Amqp\Consumable;
use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Consumer;
use Anik\Amqp\Exceptions\AmqpException;
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
        $this->channel->expects($this->timesToInvocation($times))->method('queue_declare')->will(
            $this->convertReturnToStub($return)
        );
    }

    protected function queueBindExpectation($times = null, $return = null)
    {
        $this->setMethodExpectations($this->channel, 'queue_bind', $times, $return);
    }

    protected function queueIsConsumingExpectation($times = null, $return = true)
    {
        $this->setMethodExpectations($this->channel, 'is_consuming', $times, $return);
    }

    protected function queueWaitMethodExpectation($times = null, $return = null)
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

    protected function getConsumableInstance($assert = false): Consumable
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

    public function queueDeclareDataProvider(): array
    {
        return [
            'when queue is an instance and configuration is empty' => [
                [
                    'queue' => Queue::make(
                        [
                            'name' => self::QUEUE_NAME,
                            'declare' => true,
                            'durable' => false,
                            'passive' => true,
                            'exclusive' => true,
                            'arguments' => ['key' => 'value'],
                            'ticket' => 1,
                            'no_wait' => true,
                        ]
                    ),
                    'expectations' => [
                        'name' => self::QUEUE_NAME,
                        'durable' => false,
                        'passive' => true,
                        'exclusive' => true,
                        'arguments' => ['key' => 'value'],
                        'ticket' => 1,
                        'no_wait' => true,
                    ],
                ],
            ],
            'when queue is null and configuration is non-empty' => [
                [
                    'options' => [
                        'name' => self::QUEUE_NAME,
                        'declare' => true,
                    ],
                    'expectations' => [
                        'name' => self::QUEUE_NAME,
                        'durable' => true,
                        'passive' => false,
                        'exclusive' => false,
                        'arguments' => [],
                        'ticket' => null,
                    ],
                ],
            ],
            'when queue is an instance and configuration is non-empty' => [
                [
                    'queue' => Queue::make(
                        [
                            'name' => self::QUEUE_NAME,
                            'declare' => true,
                            'durable' => false,
                            'exclusive' => false,
                        ]
                    ),
                    'options' => [
                        'arguments' => ['key' => 'value'],
                        'ticket' => 12,
                        'no_wait' => true,
                        'exclusive' => true,
                    ],
                    'expectations' => [
                        'name' => self::QUEUE_NAME,
                        'durable' => false,
                        'exclusive' => true,
                        'auto_delete' => false,
                        'no_wait' => true,
                        'arguments' => ['key' => 'value'],
                        'ticket' => 12,
                    ],
                ],
            ],
        ];
    }

    public function queueResetNameDataProvider(): array
    {
        return [
            'should not reset as name is non empty' => [
                [
                    'queue_name' => 'anik.amqp.test-name',
                    'return' => 'anik.amqp.test-name',
                    'expectation' => 'anik.amqp.test-name',
                ],
            ],
            'should reset name as name is empty' => [
                [
                    'queue_name' => '',
                    'return' => 'anik.amqp.new-name',
                    'expectation' => 'anik.amqp.new-name',
                ],
            ],
        ];
    }

    public function qosDataProvider(): array
    {
        return [
            'when qos is an instance and configuration is empty' => [
                [
                    'qos' => new Qos(5),
                    'expectations' => [
                        'times' => $this->once(),
                        'prefetch_size' => 5,
                        'prefetch_count' => 0,
                        'global' => false,
                    ],
                ],
            ],
            'when qos is null and configuration is non-empty' => [
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
            'when qos is an instance and configuration is non-empty' => [
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
            'when qos and configuration both are empty' => [
                [
                    'expectations' => [
                        'times' => $this->never(),
                    ],
                ],
            ],
        ];
    }

    public function queueBindDataProvider(): array
    {
        return [
            'when no bind options are passed' => [
                [
                    'expectations' => [
                        'queue_name' => self::QUEUE_NAME,
                        'exchange_name' => self::EXCHANGE_NAME,
                    ],
                ],
            ],
            'when bind arguments are passed' => [
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
            'with binding key' => [
                [
                    'binding_key' => 'my.binding.key',
                    'options' => [
                        'arguments' => ['key' => 'value'],
                        'ticket' => 11,
                    ],
                    'expectations' => [
                        'queue_name' => self::QUEUE_NAME,
                        'exchange_name' => self::EXCHANGE_NAME,
                        'binding_key' => 'my.binding.key',
                        'arguments' => ['key' => 'value'],
                        'ticket' => 11,
                    ],
                ],
            ],
        ];
    }

    public function consumerWaitMethodParamProvider(): array
    {
        return [
            'when no parameters are passed' => [
                [
                    'expectations' => [
                        'allowed_methods' => null,
                        'non_blocking' => false,
                        'timeout' => 0,
                    ],
                ],
            ],
            'when all values are passed' => [
                [
                    'options' => [
                        'allowed_methods' => ['60,60'],
                        'non_blocking' => true,
                        'timeout' => 10,
                    ],
                    'expectations' => [
                        'allowed_methods' => ['60,60'],
                        'non_blocking' => true,
                        'timeout' => 10,
                    ],
                ],
            ],
            'when only allowed methods are passed' => [
                [
                    'options' => [
                        'allowed_methods' => ['60,60'],
                    ],
                    'expectations' => [
                        'allowed_methods' => ['60,60'],
                    ],
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
     */
    public function testConsumerCanBeReconfiguredWithOptionsDuringConsumeMethod(array $data)
    {
        $this->exchangeDeclareExpectation($this->never());
        $this->queueDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        $this->qosExpectation($this->never());
        // Don't consume any message
        $this->queueIsConsumingExpectation($this->once(), false);
        $this->queueWaitMethodExpectation($this->never());

        $consumer = $this->getConsumer();

        $options = ($data['options'] ?? false) ? ['consumer' => $data['options']] : [];

        $name = $data['expectations']['tag'] ?? '';
        $this->channel->expects($this->once())->method('basic_consume')->with(
            $data['expectations']['queue_name'] ?? self::QUEUE_NAME,
            $this->callback(
                function ($consumerName) use ($name) {
                    return $name ? $name === $consumerName : is_string($consumerName);
                }
            ),
            $data['expectations']['no_local'] ?? false,
            $data['expectations']['no_ack'] ?? false,
            $data['expectations']['exclusive'] ?? false,
            $data['expectations']['no_wait'] ?? false,
            $this->callback(
                function ($handler) {
                    return is_callable($handler);
                }
            ),
            $data['expectations']['ticket'] ?? null,
            $data['expectations']['arguments'] ?? []
        );

        $consumer->consume(
            $this->getConsumableInstance(),
            $this->getBindingKey(),
            $this->getExchange(['declare' => false]),
            $this->getQueue(['declare' => false]),
            null,
            $options
        );
    }

    /**
     * @dataProvider exchangeDeclareDataProvider
     *
     * @param array $data
     */
    public function testConsumerConfiguresExchangeCorrectly(array $data)
    {
        $exchange = $data['exchange'] ?? null;
        $options = $data['options'] ?? [];

        $this->channel->expects($this->once())->method('exchange_declare')->with(
            $data['expectations']['name'],
            $data['expectations']['type'],
            $data['expectations']['passive'] ?? false,
            $data['expectations']['durable'] ?? true,
            $data['expectations']['auto_delete'] ?? false,
            $data['expectations']['internal'] ?? false,
            $data['expectations']['no_wait'] ?? false,
            $data['expectations']['arguments'] ?? [],
            $data['expectations']['ticket'] ?? null
        );

        $this->queueDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        $this->qosExpectation($this->never());
        // Don't consume any message
        $this->queueIsConsumingExpectation($this->once(), false);
        $this->queueWaitMethodExpectation($this->never());

        $this->getConsumer()->consume(
            $this->getConsumableInstance(),
            $this->getBindingKey(),
            $exchange,
            $this->getQueue(['declare' => false]),
            null,
            ['exchange' => $options]
        );
    }

    public function testConsumerShouldThrowExceptionIfItCannotConfigureExchange()
    {
        $this->expectException(AmqpException::class);
        $this->expectExceptionMessage('Cannot configure exchange');
        $this->getConsumer()->consume($this->getConsumableInstance());
    }

    /**
     * @dataProvider queueDeclareDataProvider
     *
     * @param array $data
     */
    public function testConsumerConfiguresQueueCorrectly(array $data)
    {
        $this->exchangeDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        $this->qosExpectation($this->never());
        // Don't consume any message
        $this->queueIsConsumingExpectation($this->once(), false);
        $this->queueWaitMethodExpectation($this->never());

        $this->channel->expects($this->once())->method('queue_declare')->with(
            $queueName = $data['expectations']['name'] ?? self::QUEUE_NAME,
            $data['expectations']['passive'] ?? false,
            $data['expectations']['durable'] ?? true,
            $data['expectations']['exclusive'] ?? false,
            $data['expectations']['auto_delete'] ?? false,
            $data['expectations']['no_wait'] ?? false,
            $data['expectations']['arguments'] ?? [],
            $data['expectations']['ticket'] ?? null
        )->willReturn([$queueName]);

        $queue = $data['queue'] ?? null;
        $options = $data['options'] ?? [];

        $this->getConsumer()->consume(
            $this->getConsumableInstance(),
            $this->getBindingKey(),
            $this->getExchange(['declare' => false]),
            $queue,
            null,
            ['queue' => $options]
        );
    }

    public function testConsumerShouldThrowExceptionIfItCannotConfigureQueue()
    {
        $this->expectException(AmqpException::class);
        $this->expectExceptionMessage('Cannot configure queue');
        $this->getConsumer()->consume(
            $this->getConsumableInstance(),
            '',
            $this->getExchange(['declare' => false])
        );
    }

    /**
     * @dataProvider queueResetNameDataProvider
     *
     * @param array $data
     */
    public function testQueueDeclareResetsQueueNameIfQueueNameIsEmptyString(array $data)
    {
        $this->exchangeDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        $this->qosExpectation($this->never());
        // Don't consume any message
        $this->queueIsConsumingExpectation($this->once(), false);
        $this->queueWaitMethodExpectation($this->never());

        $this->channel->expects($this->once())->method('queue_declare')->willReturn([$data['return']]);

        $this->getConsumer()->consume(
            $this->getConsumableInstance(),
            $this->getBindingKey(),
            $this->getExchange(['declare' => false]),
            $queue = $this->getQueue(['declare' => true, 'name' => $data['queue_name']])
        );

        $this->assertSame($data['expectation'], $queue->getName());
    }

    /**
     * @dataProvider qosDataProvider
     *
     * @param array $data
     */
    public function testConsumerConfiguresQosCorrectlyIfProvided(array $data)
    {
        $qos = $data['qos'] ?? null;
        $options = $data['options'] ?? [];

        $this->exchangeDeclareExpectation($this->never());
        $this->queueDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        // Don't consume any message
        $this->queueIsConsumingExpectation($this->once(), false);
        $this->queueWaitMethodExpectation($this->never());

        $this->channel->expects($data['expectations']['times'] ?? $this->never())
                      ->method('basic_qos')
                      ->with(
                          $data['expectations']['prefetch_size'] ?? 0,
                          $data['expectations']['prefetch_count'] ?? 0,
                          $data['expectations']['global'] ?? false
                      );

        $this->getConsumer()->consume(
            $this->getConsumableInstance(),
            $this->getBindingKey(),
            $this->getExchange(['declare' => false]),
            $this->getQueue(['declare' => false]),
            $qos,
            $options ? ['qos' => $options] : []
        );
    }

    /**
     * @dataProvider queueBindDataProvider
     *
     * @param array $data
     */
    public function testConsumerBindsQueueCorrectly(array $data)
    {
        $exchange = $data['exchange'] ?? $this->getExchange(['declare' => false]);
        $queue = $data['queue'] ?? $this->getQueue(['declare' => false]);
        $qos = $data['qos'] ?? null;
        $options = $data['options'] ?? [];

        $this->exchangeDeclareExpectation($this->never());
        $this->queueDeclareExpectation($this->never());
        $this->qosExpectation($this->never());
        // Don't consume any message
        $this->queueIsConsumingExpectation($this->once(), false);
        $this->queueWaitMethodExpectation($this->never());

        $this->channel->expects($this->once())
                      ->method('queue_bind')
                      ->with(
                          $data['expectations']['queue_name'],
                          $data['expectations']['exchange_name'],
                          $data['expectations']['binding_key'] ?? '',
                          $data['expectations']['no_wait'] ?? false,
                          $data['expectations']['arguments'] ?? [],
                          $data['expectations']['ticket'] ?? null
                      );

        $this->getConsumer()->consume(
            $this->getConsumableInstance(),
            $data['binding_key'] ?? $this->getBindingKey(''),
            $exchange,
            $queue,
            $qos,
            $options ? ['bind' => $options] : []
        );
    }

    public function testConsumerDoesNotBindToQueueIfExchangeNameIsEmpty()
    {
        $this->exchangeDeclareExpectation($this->never());
        $this->queueDeclareExpectation($this->never());
        $this->qosExpectation($this->never());
        // Don't consume any message
        $this->queueIsConsumingExpectation($this->once(), false);
        $this->queueWaitMethodExpectation($this->never());

        $this->channel->expects($this->never())
                      ->method('queue_bind');

        $this->getConsumer()->consume(
            $this->getConsumableInstance(),
            $data['binding_key'] ?? $this->getBindingKey(''),
            $this->getExchange(['name' => '', 'declare' => false]),
            $this->getQueue(['declare' => false])
        );
    }

    /**
     * @dataProvider consumerWaitMethodParamProvider
     *
     * @param array $data
     */
    public function testWhenConsumerCallsWaitMethodOnChannelUsesValuesFromTheConsumeOptionsKey(array $data)
    {
        $options = $data['options'] ?? [];

        $this->exchangeDeclareExpectation($this->never());
        $this->queueDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        $this->qosExpectation($this->never());

        $this->channel->expects($this->exactly(2))->method('is_consuming')->willReturnOnConsecutiveCalls(
            [true, true, false]
        );

        $this->channel->expects($this->exactly(1))
                      ->method('wait')
                      ->with(
                          $data['expectations']['allowed_methods'] ?? null,
                          $data['expectations']['non_blocking'] ?? false,
                          $data['expectations']['timeout'] ?? 0
                      );

        $this->getConsumer()->consume(
            $this->getConsumableInstance(),
            $this->getBindingKey(),
            $this->getExchange(['declare' => false]),
            $this->getQueue(['declare' => false]),
            null,
            $options ? ['consume' => $options] : []
        );
    }

    public function testConsumerPassesReceivedMessageToConsumableMessageHandler()
    {
        $this->exchangeDeclareExpectation($this->never());
        $this->queueDeclareExpectation($this->never());
        $this->queueBindExpectation($this->once());
        $this->qosExpectation($this->never());

        // Don't consume any message
        $this->queueIsConsumingExpectation($this->once(), false);
        $this->queueWaitMethodExpectation($this->never());

        // calls the callback, sort of reproduce the background mechanism
        $this->channel->expects($this->once())->method('basic_consume')->with($this->anything())->will(
            $this->returnCallback(
                function ($q, $t, $nl, $na, $e, $nw, $cb) {
                    $message = new AMQPMessage('message body', []);
                    $message->setChannel($this->channel);
                    call_user_func($cb, $message);
                }
            )
        );
        $this->getConsumer()->consume(
            $this->getConsumableInstance(true),
            $this->getBindingKey(),
            $this->getExchange(['declare' => false]),
            $this->getQueue(['declare' => false])
        );
    }
}
