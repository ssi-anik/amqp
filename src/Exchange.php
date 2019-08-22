<?php

namespace Anik\Amqp;

class Exchange
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
     * @return \Anik\Amqp\Exchange
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
     * @return \Anik\Amqp\Exchange
     */
    public function setProperties (array $properties) : self {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param array $properties
     *
     * @return \Anik\Amqp\Exchange
     */
    public function mergeProperties (array $properties) : self {
        $this->properties = array_merge($this->properties, $properties);

        return $this;
    }
}
