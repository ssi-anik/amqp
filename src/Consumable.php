<?php

namespace Anik\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

interface Consumable
{
    public function setMessage(AMQPMessage $message);

    public function handle(): void;
}
