<?php

namespace Anik\Amqp;

class AmqpConsumer
{
    private $properties;

    public function __construct (array $properties = []) {
        $this->setProperties($properties);
    }

    /**
     * @return array
     */
    public function getProperties () : array {
        return $this->properties;
    }

    /**
     * @param array $properties
     *
     * @return \Anik\Amqp\AmqpConsumer
     */
    public function setProperties (array $properties) : self {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param array $properties
     *
     * @return \Anik\Amqp\AmqpConsumer
     */
    public function mergeProperties (array $properties) : self {
        $this->properties = array_merge($this->properties, $properties);

        return $this;
    }
}
