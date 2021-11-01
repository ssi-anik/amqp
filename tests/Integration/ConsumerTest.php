<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Connection;
use Anik\Amqp\Consumable;
use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Consumer;
use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Exchanges\Topic;
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

    protected function getBindingKey(string $bk = ''): string
    {
        return $bk;
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
            $this->bindingKey(),
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
}
