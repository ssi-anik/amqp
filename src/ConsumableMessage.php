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

    private function assertThatMessageIsSet()
    {
        if (!$this->message) {
            throw new AmqpException('Message should be set first');
        }
    }

    public function ack(bool $multiple = false): void
    {
        $this->assertThatMessageIsSet();

        $this->message->ack($multiple);
    }

    public function nack(bool $requeue = false, bool $multiple = false): void
    {
        $this->assertThatMessageIsSet();

        $this->message->nack($requeue, $multiple);
    }

    public function reject(bool $requeue = true): void
    {
        $this->assertThatMessageIsSet();

        $this->message->reject($requeue);
    }

    public function getMessageBody(): string
    {
        $this->assertThatMessageIsSet();

        return $this->message->getBody();
    }

    public function handle(): void
    {
        $this->assertThatMessageIsSet();

        call_user_func($this->callable, $this, $this->message);
    }

    public function setMessage(AMQPMessage $message): Consumable
    {
        $this->message = $message;

        return $this;
    }
}
