<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
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

    public function ack(bool $multiple = false): bool
    {
        if (!$this->message) {
            return false;
        }

        $this->message->ack($multiple);

        return true;
    }

    public function nack(bool $requeue = false, bool $multiple = false): bool
    {
        if (!$this->message) {
            return false;
        }

        $this->message->nack($requeue, $multiple);

        return true;
    }

    public function reject(bool $requeue = true): bool
    {
        if (!$this->message) {
            return false;
        }

        $this->message->reject($requeue);

        return true;
    }

    public function getMessageBody(): string
    {
        if (!$this->message) {
            throw new AmqpException('Message is not received yet.');
        }

        return $this->message->getBody();
    }

    public function handle(): void
    {
        call_user_func($this->callable, $this, $this->message);
    }

    public function setMessage(AMQPMessage $message): Consumable
    {
        $this->message = $message;

        return $this;
    }
}
