<?php

namespace Anik\Amqp\Qos;

class Qos
{
    private $prefetchSize;
    private $prefetchCount;
    private $global;

    public function __construct(int $prefetchSize, int $prefetchCount, bool $global)
    {
        $this->prefetchSize = $prefetchSize;
        $this->prefetchCount = $prefetchCount;
        $this->global = $global;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function isGlobal(): bool
    {
        return $this->global;
    }

    public function getPrefetchSize(): int
    {
        return $this->prefetchSize;
    }
}
