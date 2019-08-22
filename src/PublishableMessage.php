<?php

namespace Anik\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

class PublishableMessage
{
    private $stream, $properties, $exchange;

    public function __construct (string $stream, array $properties = []) {
        $this->stream = $stream;
        $this->properties = $properties;
    }

    /**
     * @param array $properties
     *
     * @return \Anik\Amqp\PublishableMessage
     */
    public function setProperties (array $properties) : self {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return \PhpAmqpLib\Message\AMQPMessage
     */
    public function getAmqpMessage () : AMQPMessage {
        return new AMQPMessage($this->stream, $this->properties);
    }

    /**
     * @return string
     */
    public function getStream () : string {
        return $this->stream;
    }

    /**
     * @return array
     */
    public function getProperties () : array {
        return $this->properties;
    }

    /**
     * @return null|\Anik\Amqp\Exchange
     */
    public function getExchange () : ?Exchange {
        return $this->exchange;
    }

    /**
     * @param \Anik\Amqp\Exchange $exchange
     *
     * @return self
     */
    public function setExchange ($exchange) : self {
        $this->exchange = $exchange;

        return $this;
    }

}
