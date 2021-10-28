<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\Producer;
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
}
