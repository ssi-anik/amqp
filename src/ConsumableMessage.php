<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use PhpAmqpLib\Message\AMQPMessage;
use stdClass;

class ConsumableMessage implements Consumable
{
    /** @var AMQPMessage */
    private $message;

    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    private function ensureThatMessageIsSet()
    {
        if (is_null($this->message)) {
            throw new AmqpException('Message should be set first');
        }
    }

    public function ack(bool $multiple = false): void
    {
        $this->ensureThatMessageIsSet();

        $this->message->ack($multiple);
    }

    public function nack(bool $requeue = false, bool $multiple = false): void
    {
        $this->ensureThatMessageIsSet();

        $this->message->nack($requeue, $multiple);
    }

    public function reject(bool $requeue = true): void
    {
        $this->ensureThatMessageIsSet();

        $this->message->reject($requeue);
    }

    public function getMessageBody(): string
    {
        $this->ensureThatMessageIsSet();

        return $this->message->getBody();
    }

    public function getRoutingKey(): string
    {
        $this->ensureThatMessageIsSet();

        return $this->message->getRoutingKey();
    }

    protected function jsonDecodeMessage(bool $associative = false, int $depth = 512)
    {
        $data = json_decode($this->getMessageBody(), $associative, $depth);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    public function decodeMessage(int $depth = 512): ?array
    {
        return $this->jsonDecodeMessage(true, $depth);
    }

    public function decodeMessageAsObject(int $depth = 512): ?stdClass
    {
        return $this->jsonDecodeMessage(false, $depth);
    }

    public function handle(): void
    {
        $this->ensureThatMessageIsSet();

        call_user_func($this->callable, $this, $this->message);
    }

    public function setMessage(AMQPMessage $message): Consumable
    {
        $this->message = $message;

        return $this;
    }
    
    public function getMessage(): ?AMQPMessage {
        return this->message;
    }
}
