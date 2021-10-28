<?php

namespace Anik\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

class ConsumableMessage implements Consumable
{
    /** @var AMQPMessage */
    private $message;

    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function handle(): void
    {
        call_user_func($this->callable, $this->message);
    }

    public function setMessage(AMQPMessage $message): Consumable
    {
        $this->message = $message;

        return $this;
    }
}
