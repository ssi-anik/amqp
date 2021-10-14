<?php

namespace Anik\Amqp\Qos;

class Qos
{
    private $prefetchSize;
    private $prefetchCount;
    private $global;

    public function __construct(?int $prefetchSize = 0, int $prefetchCount = 0, ?bool $global = false)
    {
        $this->prefetchSize = $prefetchSize;
        $this->prefetchCount = $prefetchCount;
        $this->global = $global;
    }

    public function getPrefetchCount(): ?int
    {
        return $this->prefetchCount;
    }

    public function isGlobal(): ?bool
    {
        return $this->global;
    }

    public function getPrefetchSize(): int
    {
        return $this->prefetchSize;
    }
}
