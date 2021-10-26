<?php

namespace Anik\Amqp\Tests\Integration;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;
use PHPUnit\Framework\TestCase;

class AmqpTest extends TestCase
{
    protected $connection;
    protected $channel;

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

    protected function getAmqpChannelMock()
    {
        return $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getConnectionMock();
        $this->channel = $this->getChannelMock();
    }
}
