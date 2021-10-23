<?php

namespace Anik\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

class ConsumableMessage implements Consumable
{
    private $message;

    public function handle(): void
    {
        var_dump($this->message->getBody());
    }

    public function setMessage(AMQPMessage $message): self
    {
        return $this;
    }
}
