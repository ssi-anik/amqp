<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Connection;
use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Direct;
use Anik\Amqp\Exchanges\Fanout;
use Anik\Amqp\Exchanges\Headers;
use Anik\Amqp\Exchanges\Topic;
use Anik\Amqp\Producer;
use Anik\Amqp\Producible;
use Anik\Amqp\ProducibleMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ProducerTest extends AmqpTestCase
{
    protected function getProducibleMessage($message = 'anik.amqp.msg', array $properties = []): Producible
    {
        return new ProducibleMessage($message, $properties);
    }

    protected function getProducer(?AbstractConnection $connection = null, ?AMQPChannel $channel = null): Producer
    {
        return new Producer($connection ?? $this->connection, $channel ?? $this->channel);
    }

    protected function publishBatchExpectation(
        $exchangeName,
        $routingKey,
        $mandatory,
        $immediate,
        $ticket,
        $times = 1
    ) {
        $this->channel->expects($this->timesToInvocation($times))->method('batch_basic_publish')->with(
            $this->callback(
                function ($msg) {
                    return $msg instanceof AMQPMessage;
                }
            ),
            $exchangeName,
            $routingKey,
            $mandatory,
            $immediate,
            $ticket
        );
        $this->channel->expects($this->any())->method('publish_batch')->willReturn(true);
    }

    public function publishMessageDataProvider(): array
    {
        return [
            'exchange passed as parameter' => [
                [
                    'exchange' => $this->getExchange(['declare' => false]),
                    'expectations' => [
                        'times' => $this->never(),
                    ],
                ],
            ],
            'should not declare exchange declared with false' => [
                [
                    'exchange' => $this->getExchange(['declare' => false]),
                    'expectations' => [
                        'times' => $this->never(),
                    ],
                ],
            ],
            'should declare exchange when declare is true' => [
                [
                    'exchange' => $this->getExchange(['declare' => true]),
                    'expectations' => [
                        'times' => $this->once(),
                    ],
                ],
            ],
            'when exchange is null it should create instance from options' => [
                [
                    'options' => [
                        'exchange' => $this->exchangeOptions(['declare' => true]),
                    ],
                    'expectations' => [
                        'times' => $this->once(),
                        'exchange_name' => self::EXCHANGE_NAME,
                    ],
                ],
            ],
            'when exchange is created from options and declare is false' => [
                [
                    'options' => [
                        'exchange' => $this->exchangeOptions(['declare' => false]),
                    ],
                    'expectations' => [
                        'times' => $this->never(),
                        'exchange_name' => self::EXCHANGE_NAME,
                    ],
                ],
            ],
            'exchange can be reconfigured through options' => [
                [
                    'exchange' => $this->getExchange(),
                    'options' => [
                        'exchange' => ['declare' => false],
                    ],
                    'expectations' => [
                        'times' => $this->never(),
                    ],
                ],
            ],
            'with fanout exchange' => [
                [
                    'exchange' => Fanout::make(['name' => self::EXCHANGE_NAME]),
                    'options' => [
                        'exchange' => ['declare' => true],
                    ],
                    'expectations' => [
                        'times' => $this->once(),
                    ],
                ],
            ],
            'with topic exchange' => [
                [
                    'exchange' => Topic::make(['name' => self::EXCHANGE_NAME]),
                    'options' => [
                        'exchange' => ['declare' => false],
                    ],
                    'expectations' => [
                        'times' => $this->never(),
                    ],
                ],
            ],
            'with headers exchange' => [
                [
                    'exchange' => Headers::make(['name' => self::EXCHANGE_NAME]),
                    'options' => [
                        'exchange' => ['declare' => false],
                    ],
                    'expectations' => [
                        'times' => $this->never(),
                    ],
                ],
            ],
            'with direct exchange' => [
                [
                    'exchange' => Direct::make(['name' => self::EXCHANGE_NAME]),
                    'options' => [
                        'exchange' => ['declare' => true],
                    ],
                    'expectations' => [
                        'times' => $this->once(),
                    ],
                ],
            ],
            'mandatory, immediate, ticket can be set through options with key publish' => [
                [
                    'exchange' => $this->getExchange(['declare' => false]),
                    'options' => [
                        'publish' => [
                            'mandatory' => true,
                            'immediate' => true,
                            'ticket' => 5,
                        ],
                    ],
                    'expectations' => [
                        'times' => $this->never(),
                        'mandatory' => true,
                        'immediate' => true,
                        'ticket' => 5,
                    ],
                ],
            ],
        ];
    }

    public function testPublisherIsAChildOfConnection()
    {
        $this->assertInstanceOf(Connection::class, $this->getProducer());
    }

    /**
     * @dataProvider publishMessageDataProvider
     *
     * @param array $data
     */
    public function testPublish(array $data)
    {
        $msg = $data['message'] ?? $this->getProducibleMessage();
        $routingKey = $data['routing_key'] ?? $this->getRoutingKey();
        $exchange = $data['exchange'] ?? null;

        if ($data['expectations']['times'] ?? null) {
            $this->exchangeDeclareExpectation($data['expectations']['times']);
        } else {
            $this->exchangeDeclareExpectation($this->never());
        }

        $this->publishBatchExpectation(
            $data['expectations']['exchange_name'] ?? self::EXCHANGE_NAME,
            $data['expectations']['routing_key'] ?? $this->getRoutingKey(),
            $data['expectations']['mandatory'] ?? false,
            $data['expectations']['immediate'] ?? false,
            $data['expectations']['ticket'] ?? null
        );

        $options = [];
        if ($data['options']['exchange'] ?? false) {
            $options['exchange'] = $data['options']['exchange'];
        }

        if ($data['options']['publish'] ?? []) {
            $options['publish'] = $data['options']['publish'];
        }

        $this->getProducer()->publish($msg, $routingKey, $exchange, $options);
    }

    /**
     * @dataProvider publishMessageDataProvider
     *
     * @param array $data
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testPublishBatch(array $data)
    {
        $messages = $data['message'] ?? $this->getProducibleMessage();
        $routingKey = $data['routing_key'] ?? $this->getRoutingKey();
        $exchange = $data['exchange'] ?? null;

        if ($data['expectations']['times'] ?? null) {
            $this->exchangeDeclareExpectation($data['expectations']['times']);
        } else {
            $this->exchangeDeclareExpectation($this->never());
        }

        $this->publishBatchExpectation(
            $data['expectations']['exchange_name'] ?? self::EXCHANGE_NAME,
            $data['expectations']['routing_key'] ?? $this->getRoutingKey(),
            $data['expectations']['mandatory'] ?? false,
            $data['expectations']['immediate'] ?? false,
            $data['expectations']['ticket'] ?? null
        );

        $options = [];
        if ($data['options']['exchange'] ?? false) {
            $options['exchange'] = $data['options']['exchange'];
        }

        if ($data['options']['publish'] ?? []) {
            $options['publish'] = $data['options']['publish'];
        }

        $this->getProducer()->publishBatch(
            is_array($messages) ? $messages : [$messages],
            $routingKey,
            $exchange,
            $options
        );
    }

    public function testPublishBatchWhenMessageIsNotAnImplementationOfProducible()
    {
        $exchange = Fanout::make(['name' => self::EXCHANGE_NAME, 'declare' => false]);
        $this->expectException(AmqpException::class);
        $this->getProducer()->publishBatch(['anik.amqp.string.msg'], '', $exchange);
    }

    public function testPublishBatchDefiningBatchCountNumber()
    {
        $exchange = Fanout::make(['name' => self::EXCHANGE_NAME, 'declare' => false]);

        $this->channel->expects($this->exactly(3))->method('batch_basic_publish');
        $this->channel->expects($this->exactly(2))->method('publish_batch');

        $this->getProducer()->publishBatch(
            [
                $this->getProducibleMessage(),
                $this->getProducibleMessage(),
                $this->getProducibleMessage(),
            ],
            '',
            $exchange,
            ['publish' => ['batch_count' => 2]]
        );
    }

    public function testPublishBatchDoesNotSendMessageIfMessageCountIsZeroArePassed()
    {
        $this->setMethodExpectationsOnChannel(
            [
                'batch_basic_publish' => ['times' => $this->never()],
                'publish_batch' => ['times' => $this->never()],
            ]
        );
        $this->getProducer()->publishBatch([]);
    }

    /**
     * @dataProvider publishMessageDataProvider
     *
     * @param array $data
     */
    public function testPublishBasic(array $data)
    {
        $msg = $data['message'] ?? $this->getProducibleMessage();
        $routingKey = $data['routing_key'] ?? $this->getRoutingKey();
        $exchange = $data['exchange'] ?? null;

        if ($data['expectations']['times'] ?? null) {
            $this->exchangeDeclareExpectation($data['expectations']['times']);
        } else {
            $this->exchangeDeclareExpectation($this->never());
        }

        $this->channel->expects($this->once())->method('basic_publish')->with(
            $this->callback(
                function ($msg) {
                    return $msg instanceof AMQPMessage;
                }
            ),
            $data['expectations']['exchange_name'] ?? self::EXCHANGE_NAME,
            $data['expectations']['routing_key'] ?? $this->getRoutingKey(),
            $data['expectations']['mandatory'] ?? false,
            $data['expectations']['immediate'] ?? false,
            $data['expectations']['ticket'] ?? null
        );

        $options = [];
        if ($data['options']['exchange'] ?? false) {
            $options['exchange'] = $data['options']['exchange'];
        }

        if ($data['options']['publish'] ?? []) {
            $options['publish'] = $data['options']['publish'];
        }

        $this->getProducer()->publishBasic($msg, $routingKey, $exchange, $options);
    }

    /**
     * @dataProvider exchangeDeclareDataProvider
     *
     * @param array $data
     */
    public function testPublishCallsExchangeDeclareWithCorrectData(array $data)
    {
        $message = $this->getProducibleMessage();
        $routingKey = $this->getRoutingKey();
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

        $this->setMethodExpectations($this->channel, 'batch_basic_publish', null, null);
        $this->setMethodExpectations($this->channel, 'publish_batch', null, null);

        $this->getProducer()->publish($message, $routingKey, $exchange, ['exchange' => $options]);
    }

    /**
     * @dataProvider exchangeDeclareDataProvider
     *
     * @param array $data
     */
    public function testPublishBasicCallsExchangeDeclareWithCorrectData(array $data)
    {
        $messages = $this->getProducibleMessage();
        $routingKey = $this->getRoutingKey();
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

        $this->setMethodExpectations($this->channel, 'basic_publish', null, null);

        $this->getProducer()->publishBasic($messages, $routingKey, $exchange, ['exchange' => $options]);
    }

    /**
     * @dataProvider exchangeDeclareDataProvider
     *
     * @param array $data
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testPublishBatchCallsExchangeDeclareWithCorrectData(array $data)
    {
        $messages = [$this->getProducibleMessage()];
        $routingKey = $this->getRoutingKey();
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

        $this->setMethodExpectations($this->channel, 'batch_basic_publish', null, null);
        $this->setMethodExpectations($this->channel, 'publish_batch', null, null);

        $this->getProducer()->publishBatch($messages, $routingKey, $exchange, ['exchange' => $options]);
    }

    public function testPublishThrowsExceptionIfNoneOfExchangeAndOptionForExchangeIsNotPassed()
    {
        $this->expectException(AmqpException::class);
        $this->getProducer()->publish($this->getProducibleMessage(), $this->getRoutingKey(), null, []);
    }

    public function testPublishBatchThrowsExceptionIfNoneOfExchangeAndOptionForExchangeIsNotPassed()
    {
        $this->expectException(AmqpException::class);
        $this->getProducer()->publishBatch([$this->getProducibleMessage()], $this->getRoutingKey(), null, []);
    }

    public function testPublishBasicThrowsExceptionIfNoneOfExchangeAndOptionForExchangeIsNotPassed()
    {
        $this->expectException(AmqpException::class);
        $this->getProducer()->publishBasic($this->getProducibleMessage(), $this->getRoutingKey(), null, []);
    }
}
