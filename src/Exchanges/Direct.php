<?php

namespace Anik\Amqp\Exchanges;

class Direct extends Exchange
{
    public function __construct(string $name)
    {
        parent::__construct($name, self::TYPE_DIRECT);
    }

    public static function make(array $options): Exchange
    {
        return parent::make(['type' => self::TYPE_DIRECT] + $options);
    }

    public function setType($type): Exchange
    {
        $this->type = self::TYPE_DIRECT;

        return $this;
    }
}
