<?php

namespace Anik\Amqp\Producer;

use PhpAmqpLib\Message\AMQPMessage;

interface ProducerMessageInterface
{
    public function prepare(): AMQPMessage;
}
