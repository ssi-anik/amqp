<?php

namespace Anik\Amqp;

class Queue
{
    private $name, $properties;

    public function __construct (string $name, array $properties = []) {
        $this->setName($name);
        $this->setProperties($properties);
    }

    /**
     * @return string
     */
    public function getName () : string {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return \Anik\Amqp\Queue
     */
    public function setName (string $name) : self {
        $this->name = $name;

        return $this;
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
     * @return \Anik\Amqp\Queue
     */
    public function setProperties (array $properties) : self {
        $this->properties = $properties;

        return $this;
    }
}
