<?php

namespace Anik\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

class ProducibleMessage implements Producible
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

    public function build(): AMQPMessage
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
