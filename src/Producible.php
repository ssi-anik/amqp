<?php

namespace Anik\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

interface Producible
{
    public function build(): AMQPMessage;
}
