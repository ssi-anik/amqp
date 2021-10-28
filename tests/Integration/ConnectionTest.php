<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Direct;
use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Exchanges\Fanout;
use Anik\Amqp\Producer;
use Closure;
use Exception;

class ConnectionTest extends AmqpTestCase
{
    public function testConstructConnectionWithOnlyAmqpConnection()
    {
        $this->setMethodExpectationsOnConnection(
            [
                'channel' => ['times' => $this->never()],
            ]
        );

        new Producer($this->connection);
    }

    public function testConstructConnectionWithOnlyAmqpConnectionCallsGetChannelMethodWhenConnectOnConstructIsTrue()
    {
        $connection = $this->connection;
        $this->setMethodExpectationsOnConnection(
            [
                'connectOnConstruct' => ['times' => 1, 'return' => true],
                'channel' => ['times' => 1, 'return' => $this->channel],
            ]
        );

        $producer = new Producer($connection);
        $this->assertSame($this->channel, $producer->getChannel());
    }

    public function testConstructConnectionWithOnlyAmqpConnectionTriesToGetChannelFromAmqpConnection()
    {
        $channel = $this->setMethodExpectations($this->getAmqpChannelMock(), 'getChannelId', $this->exactly(2), 5);
        $this->setMethodExpectationsOnConnection(
            [
                'channel' => ['times' => $this->once(), 'return' => $channel],
            ]
        );

        $producer = new Producer($this->connection);
        $this->assertSame($channel, $producer->getChannel());
        $this->assertEquals(5, $producer->getChannel()->getChannelId());
    }

    public function testConstructConnectionWithAmqpConnectionAndChannel()
    {
        $producer = new Producer($this->connection, $this->channel);
        $this->assertSame($this->channel, $producer->getChannel());
    }

    public function testWhenDestructingObjectConnectionWillCallCloseMethodIfAmqpHasAnActiveConnection()
    {
        $this->setMethodExpectationsOnConnection(
            [
                'isConnected' => ['times' => 1, 'return' => true],
                'close' => ['times' => 1],
            ]
        );
        new Producer($this->connection);
    }

    public function testWhenDestructingObjectConnectionWillNotCallCloseMethodIfConnectionIsNotActive()
    {
        $this->setMethodExpectationsOnConnection(
            [
                'isConnected' => ['times' => 1, 'return' => false],
                'close' => ['times' => $this->never()],
            ]
        );
        new Producer($this->connection);
    }

    public function testWhenDestructingObjectConnectionWillCallChannelCloseMethodIfChannelIsOpen()
    {
        $this->setMethodExpectationsOnChannel(
            [
                'is_open' => ['times' => 1, 'return' => true],
                'close' => ['times' => 1],
            ]
        );

        new Producer($this->connection, $this->channel);
    }

    public function testWhenDestructingObjectConnectionWillNotCallChannelCloseMethodIfChannelIsNotOpen()
    {
        $this->setMethodExpectationsOnChannel(
            [
                'is_open' => ['times' => 1, 'return' => false],
                'close' => ['times' => $this->never()],
            ]
        );

        new Producer($this->connection, $this->channel);
    }

    public function testThrowingExceptionWhenClosingConnectionDoesNotCrashApplication()
    {
        $this->setMethodExpectationsOnChannel(
            [
                'is_open' => ['return' => true],
            ]
        );
        $this->setMethodExpectationsOnConnection(
            [
                'isConnected' => ['return' => true,],
            ]
        );
        $this->connection->expects($this->once())->method('close')->willThrowException(
            new Exception('Application should crash')
        );
        $this->channel->expects($this->once())->method('close')->willThrowException(
            new Exception('Application should crash')
        );

        new Producer($this->connection, $this->channel);
    }

    public function testChannelCanBeSetOnConnectionFromOutsideTheClass()
    {
        $newChannel = $this->getChannelMock();
        $producer = new Producer($this->connection, $this->channel);
        $producer->setChannel($newChannel);
        $this->assertNotSame($this->channel, $producer->getChannel());
        $this->assertSame($newChannel, $producer->getChannel());
    }

    public function testConnectionIsAbleToMakeNewChannelWithIdFromAmqpConnection()
    {
        $this->setMethodExpectationsOnConnection(
            [
                'channel' => ['times' => 1, 'return' => $this->channel],
            ]
        );

        $producer = new Producer($this->connection);
        $this->assertSame($this->channel, $producer->getChannelWithId(5));
    }

    /**
     * @dataProvider exchangeConfigureDataProvider
     *
     * @param array $data
     */
    public function testConnectionIsAbleToMakeOrConfigureExchange(array $data)
    {
        $exchange = $data['exchange'] ?? null;
        $options = $data['options'] ?? [];
        $checks = $data['checks'] ?? [];
        $producer = new Producer($this->connection);

        $configuredExchange = Closure::fromCallable(
            function () use ($exchange, $options) {
                return $this->makeOrReconfigureExchange($exchange, $options);
            }
        )->call($producer);

        foreach ($checks as $method => $expectation) {
            $this->assertEquals($configuredExchange->$method(), $expectation);
        }
    }

    public function testNameAndTypeIsRequiredWhenConnectionMakesExchangeFromOptions()
    {
        $this->expectException(AmqpException::class);
        $producer = new Producer($this->connection);

        Closure::fromCallable(
            function () {
                return $this->makeOrReconfigureExchange(null, []);
            }
        )->bindTo($producer)->call($producer);
    }

    public function exchangeConfigureDataProvider(): array
    {
        return [
            'should create an exchange' => [
                [
                    'exchange' => null,
                    'options' => [
                        'name' => self::EXCHANGE_NAME,
                        'type' => Exchange::TYPE_DIRECT,
                    ],
                    'checks' => [
                        'shouldDeclare' => false,
                        'isPassive' => false,
                        'isAutoDelete' => false,
                        'isDurable' => true,
                    ],
                ],
            ],
            'should reconfigure exchange if provided to the method' => [
                [
                    'exchange' => new Fanout(self::EXCHANGE_NAME),
                    'options' => [
                        'durable' => false,
                        'auto_delete' => true,
                        'internal' => true,
                        'arguments' => ['key' => 'value'],
                        'ticket' => 20,
                    ],
                    'checks' => [
                        'getName' => self::EXCHANGE_NAME,
                        'isAutoDelete' => true,
                        'isInternal' => true,
                        'isDurable' => false,
                        'getArguments' => ['key' => 'value'],
                        'getTicket' => 20,
                    ],
                ],
            ],
            'exchange is not reconfigured if options are not provided' => [
                [
                    'exchange' => new Direct(self::EXCHANGE_NAME),
                    'checks' => [
                        'getName' => self::EXCHANGE_NAME,
                        'isAutoDelete' => false,
                        'isInternal' => false,
                        'isDurable' => true,
                        'getArguments' => [],
                        'getTicket' => null,
                    ],
                ],
            ],
        ];
    }
}
