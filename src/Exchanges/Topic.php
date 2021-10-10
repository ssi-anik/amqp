<?php

namespace Anik\Amqp\Exchanges;

class Topic extends Exchange
{
    public function __construct(string $name)
    {
        parent::__construct($name, self::TYPE_TOPIC);
    }

    public function setType($type): Exchange
    {
        $this->type = self::TYPE_TOPIC;

        return $this;
    }
}
