<?php

namespace Anik\Amqp\Exchanges;

class Fanout extends Exchange
{
    public function __construct(string $name)
    {
        parent::__construct($name, self::TYPE_FANOUT);
    }

    public static function make(array $options): Exchange
    {
        return parent::make(['type' => self::TYPE_FANOUT] + $options);
    }

    public function setType($type): Exchange
    {
        $this->type = self::TYPE_FANOUT;

        return $this;
    }
}
