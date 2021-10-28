<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Producer;

class ConnectionTest extends AmqpTestCase
{
    public function testInstantiateConnectionWithOnlyAmqpConnection()
    {
        $connection = $this->connection;
        $this->setMethodExpectationsOnConnection(
            [
                'channel' => ['times' => $this->never(), 'return' => null],
            ]
        );

        new Producer($connection);
    }

    public function testInstantiateConnectionWithOnlyAmqpConnectionCallsGetChannelWhenConnectOnConstructIsTrue()
    {
        $connection = $this->connection;
        $this->setMethodExpectationsOnConnection(
            [
                'channel' => ['times' => 1, 'return' => $this->channel],
                'connectOnConstruct' => ['times' => 1, 'return' => true],
            ]
        );

        $producer = new Producer($connection);
        $this->assertSame($this->channel, $producer->getChannel());
    }

    public function testInstantiateConnectionWithOnlyAmqpConnectionTriesToGetChannelFromAmqpConnection()
    {
        $this->setMethodExpectationsOnChannel(
            [
                'getChannelId' => ['times' => $this->never(),],
            ]
        );
        $this->setMethodExpectationsOnConnection(
            [
                'channel' => ['times' => $this->once(), 'return' => $this->channel],
            ]
        );

        $producer = new Producer($this->connection);
        $this->assertSame($this->channel, $producer->getChannel());
    }

    public function testInstantiateConnectionWithAmqpConnectionAndChannel()
    {
        $producer = new Producer($this->connection, $this->channel);
        $this->assertSame($this->channel, $producer->getChannel());
    }
}
