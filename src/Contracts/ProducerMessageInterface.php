<?php

namespace Anik\Amqp\Contracts;

use PhpAmqpLib\Message\AMQPMessage;

interface ProducerMessageInterface
{
    public function prepare(): AMQPMessage;
}
