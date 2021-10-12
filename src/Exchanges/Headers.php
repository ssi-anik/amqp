<?php

namespace Anik\Amqp\Exchanges;

class Headers extends Exchange
{
    public function __construct(string $name)
    {
        parent::__construct($name, self::TYPE_HEADERS);
    }

    public function setType($type): Exchange
    {
        $this->type = self::TYPE_HEADERS;

        return $this;
    }
}
