<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Producer;

class ConnectionTest extends AmqpTest
{
    public function testInstantiateConnectionWithOnlyAmqpConnection()
    {
        $connection = $this->connection;

        $connection->expects($this->never())->method('channel');

        new Producer($connection);
    }

    public function testInstantiateConnectionWithOnlyAmqpConnectionCallsGetChannelWhenConnectOnConstructIsTrue()
    {
        $connection = $this->connection;
        $connection->expects($this->once())->method('connectOnConstruct')->willReturn(true);
        $connection->expects($this->once())->method('channel')->willReturn($this->channel);

        $producer = new Producer($connection);
        $this->assertSame($this->channel, $producer->getChannel());
    }

    public function testInstantiateConnectionWithOnlyAmqpConnectionTriesToGetChannelFromAmqpConnection()
    {
        $connection = $this->connection;
        $channel = $this->channel;
        $channel->expects($this->never())->method('getChannelId');
        $connection->expects($this->once())->method('channel')->willReturn($this->channel);

        $producer = new Producer($connection);
        $this->assertSame($this->channel, $producer->getChannel());
    }

    public function testInstantiateConnectionWithAmqpConnectionAndChannel()
    {
        $producer = new Producer($this->connection, $this->channel);
        $this->assertSame($this->channel, $producer->getChannel());
    }
}
