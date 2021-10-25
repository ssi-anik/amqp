<?php

namespace Anik\Amqp\Tests;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;
use PHPUnit\Framework\TestCase;

class AmqpTest extends TestCase
{
    public function getConnectionMock($class = AMQPLazySSLConnection::class): AbstractConnection
    {
        if (!is_subclass_of($class, AbstractConnection::class)) {
            throw new \Exception('Must be an implementation of PhpAmqpLib\Connection\AbstractConnection::class');
        }

        return $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
    }

    public function getChannelMock(): AMQPChannel
    {
        return $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();
    }
}
