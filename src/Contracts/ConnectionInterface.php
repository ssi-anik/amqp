<?php

namespace Anik\Amqp\Contracts;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

interface ConnectionInterface
{
    public function getConnection(): AbstractConnection;

    public function getChannel(): AMQPChannel;
}
