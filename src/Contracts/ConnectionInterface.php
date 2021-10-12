<?php

namespace Anik\Amqp\Contracts;

use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Connection\AbstractConnection;

interface ConnectionInterface
{
    public function getConnection(): AbstractConnection;

    public function getChannel(): AbstractChannel;
}
