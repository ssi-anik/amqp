<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Connection;
use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Direct;
use Anik\Amqp\Exchanges\Exchange;
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
    protected function getMessage($message = 'anik.amqp.msg', array $properties = []): Producible
    {
        return new ProducibleMessage($message, $properties);
    }

    protected function getProducer(?AbstractConnection $connection = null, ?AMQPChannel $channel = null): Producer
    {
        return new Producer($connection ?? $this->connection, $channel ?? $this->channel);
    }

    protected function publishExpectation(
        $expectedMessage,
        $expectedExchangeName = ProducerTest::EXCHANGE_NAME,
        $expectedRoutingKey = ProducerTest::ROUTING_KEY,
        $expectedMandatory = false,
        $expectedImmediate = false,
        $expectedTicket = null,
        $method = 'batch_basic_publish',
        $times = 1
    ) {
        $this->setMethodExpectationsOnChannel(
            [
                $method => [
                    'times' => $times,
                    'checks' => $this->returnCallback(
                        function (
                            $msg,
                            $en,
                            $rk,
                            $mandatory,
                            $immediate,
                            $ticket
                        ) use (
                            $expectedMessage,
                            $expectedExchangeName,
                            $expectedRoutingKey,
                            $expectedMandatory,
                            $expectedImmediate,
                            $expectedTicket
                        ) {
                            $this->assertInstanceOf(AMQPMessage::class, $msg);
                            $this->assertEquals($expectedRoutingKey, $rk);
                            $this->assertEquals($expectedExchangeName, $en);
                            $this->assertEquals($expectedMandatory, $mandatory);
                            $this->assertEquals($expectedImmediate, $immediate);
                            $this->assertEquals($expectedTicket, $ticket);
                        }
                    ),
                ],
            ]
        );

        if ($method === 'batch_basic_publish') {
            $this->setMethodExpectationsOnChannel(
                [
                    'publish_batch' => ['times' => $this->any(), 'return' => true],
                ]
            );
        }
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

    public function publishMessageDataProvider(): array
    {
        return [
            'exchange passed as parameter' => [
                [
                    'exchange' => $this->getExchange(['declare' => false]),
                ],
            ],
            'should not declare exchange declared with false' => [
                [
                    'exchange' => $this->getExchange(['declare' => false]),
                    'expectations' => [
                        'exchange' => ['times' => $this->never()],
                    ],
                ],
            ],
            'should declare exchange when declare is true' => [
                [
                    'exchange' => $this->getExchange(['declare' => true]),
                    'expectations' => [
                        'exchange' => ['times' => $this->once()],
                    ],
                ],
            ],
            'when exchange is null it should create instance from options' => [
                [
                    'options' => [
                        'exchange' => ($options = $this->exchangeOptions(['declare' => true])),
                    ],
                    'expectations' => [
                        'exchange' => ['times' => $this->once()],
                        'exchange_name' => $options['name'],
                    ],
                ],
            ],
            'when exchange is created from options and declare is false' => [
                [
                    'options' => [
                        'exchange' => ($options = $this->exchangeOptions(['declare' => false])),
                    ],
                    'expectations' => [
                        'exchange' => ['times' => $this->never()],
                        'exchange_name' => $options['name'],
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
                        'exchange' => ['times' => $this->never()],
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
                        'exchange' => ['times' => $this->once(),],
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
                        'exchange' => ['times' => $this->never(),],
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
                        'exchange' => ['times' => $this->never(),],
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
                        'exchange' => ['times' => $this->once(),],
                    ],
                ],
            ],
            'with default exchange' => [
                [
                    'exchange' => Exchange::make(['name' => '', 'type' => '']),
                    'options' => [
                        'exchange' => ['declare' => true],
                    ],
                    'expectations' => [
                        'exchange' => ['times' => $this->once(),],
                    ],
                ],
            ],
            'mandatory, immediate, ticket can be set through options with key publish' => [
                [
                    'exchange' => $this->getExchange(),
                    'options' => [
                        'publish' => [
                            'mandatory' => true,
                            'immediate' => true,
                            'ticket' => 5,
                        ],
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
    public function testPublishBasic(array $data)
    {
        $msg = $data['message'] ?? $this->getMessage();
        $routingKey = $data['routing_key'] ?? $this->routingKey();
        $exchange = $data['exchange'] ?? null;
        $mandatory = $data['options']['publish']['mandatory'] ?? false;
        $immediate = $data['options']['publish']['immediate'] ?? false;
        $ticket = $data['options']['publish']['ticket'] ?? null;
        $this->exchangeDeclareExpectation($data['expectations']['exchange']['times'] ?? null);

        $this->publishExpectation(
            $msg,
            $exchange instanceof Exchange ? $exchange->getName() : ($data['expectations']['exchange_name'] ?? ''),
            $routingKey,
            $mandatory,
            $immediate,
            $ticket,
            'basic_publish'
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
    public function testPublishBasicCallsExchangeDeclareWithCorrectData(array $data)
    {
        $messages = $this->getMessage();
        $routingKey = $this->routingKey();
        $exchange = $data['exchange'] ?? null;
        $options = $data['options'] ?? [];

        if ($data['expectations']['exception'] ?? false) {
            $this->expectException(AmqpException::class);
        } else {
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

        $this->setMethodExpectations($this->channel, 'basic_publish', null, null);

        $this->getProducer()->publishBasic($messages, $routingKey, $exchange, ['exchange' => $options]);
    }

    /**
     * @dataProvider publishMessageDataProvider
     *
     * @param array $data
     */
    public function testPublish(array $data)
    {
        $msg = $data['message'] ?? $this->getMessage();
        $routingKey = $data['routing_key'] ?? $this->routingKey();
        $exchange = $data['exchange'] ?? null;
        $mandatory = $data['options']['publish']['mandatory'] ?? false;
        $immediate = $data['options']['publish']['immediate'] ?? false;
        $ticket = $data['options']['publish']['ticket'] ?? null;
        $this->exchangeDeclareExpectation($data['expectations']['exchange']['times'] ?? null);

        $this->publishExpectation(
            $msg,
            $exchange instanceof Exchange ? $exchange->getName() : ($data['expectations']['exchange_name'] ?? ''),
            $routingKey,
            $mandatory,
            $immediate,
            $ticket
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
     * @dataProvider exchangeDeclareDataProvider
     *
     * @param array $data
     */
    public function testPublishCallsExchangeDeclareWithCorrectData(array $data)
    {
        $messages = $this->getMessage();
        $routingKey = $this->routingKey();
        $exchange = $data['exchange'] ?? null;
        $options = $data['options'] ?? [];

        if ($data['expectations']['exception'] ?? false) {
            $this->expectException(AmqpException::class);
        } else {
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

        $this->setMethodExpectations($this->channel, 'basic_publish', null, null);

        $this->getProducer()->publish($messages, $routingKey, $exchange, ['exchange' => $options]);
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
        $messages = $data['message'] ?? $this->getMessage();
        $messages = is_array($messages) ? $messages : [$messages];
        $routingKey = $data['routing_key'] ?? $this->routingKey();
        $exchange = $data['exchange'] ?? null;
        $mandatory = $data['options']['publish']['mandatory'] ?? false;
        $immediate = $data['options']['publish']['immediate'] ?? false;
        $ticket = $data['options']['publish']['ticket'] ?? null;
        $this->exchangeDeclareExpectation($data['expectations']['exchange']['times'] ?? null);

        $this->publishExpectation(
            $messages,
            $exchange instanceof Exchange ? $exchange->getName() : ($data['expectations']['exchange_name'] ?? ''),
            $routingKey,
            $mandatory,
            $immediate,
            $ticket
        );

        $options = [];
        if ($data['options']['exchange'] ?? false) {
            $options['exchange'] = $data['options']['exchange'];
        }

        if ($data['options']['publish'] ?? []) {
            $options['publish'] = $data['options']['publish'];
        }

        $this->getProducer()->publishBatch($messages, $routingKey, $exchange, $options);
    }

    /**
     * @dataProvider exchangeDeclareDataProvider
     *
     * @param array $data
     */
    public function testPublishBatchCallsExchangeDeclareWithCorrectData(array $data)
    {
        $messages = [$this->getMessage()];
        $routingKey = $this->routingKey();
        $exchange = $data['exchange'] ?? null;
        $options = $data['options'] ?? [];

        if ($data['expectations']['exception'] ?? false) {
            $this->expectException(AmqpException::class);
        } else {
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

        $this->setMethodExpectations($this->channel, 'basic_publish', null, null);

        $this->getProducer()->publishBatch($messages, $routingKey, $exchange, ['exchange' => $options]);
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
        $this->setMethodExpectationsOnChannel(
            [
                'batch_basic_publish' => ['times' => $this->exactly(3)],
                'publish_batch' => ['times' => $this->exactly(2)],
            ]
        );
        $this->getProducer()->publishBatch(
            [
                $this->getMessage(),
                $this->getMessage(),
                $this->getMessage(),
            ],
            '',
            $exchange,
            ['publish' => ['batch_count' => 2]]
        );
    }

    public function testPublishBatchDoesNotSendMessageIfEmptyMessagesArePassed()
    {
        $this->setMethodExpectationsOnChannel(
            [
                'batch_basic_publish' => ['times' => $this->never()],
                'publish_batch' => ['times' => $this->never()],
            ]
        );
        $this->getProducer()->publishBatch([]);
    }
}
