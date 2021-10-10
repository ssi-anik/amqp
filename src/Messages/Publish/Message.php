<?php

namespace Anik\Amqp\Messages\Publish;

use PhpAmqpLib\Message\AMQPMessage;

class Message
{
    private $message;
    private $properties = [];

    public function __construct($message = '', array $properties = [])
    {
        $this->setMessage($message);
        $this->setProperties($properties);
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message): self
    {
        $this->message = $message;

        return $this;
    }

    public function prepare(): AMQPMessage
    {
        return new AMQPMessage($this->getMessage(), $this->getProperties());
    }

    protected function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }
}
