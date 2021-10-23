<?php

namespace Anik\Amqp\Qos;

class Qos
{
    private $prefetchSize;
    private $prefetchCount;
    private $global;

    public function __construct(int $prefetchSize = 0, int $prefetchCount = 0, bool $global = false)
    {
        $this->prefetchSize = $prefetchSize;
        $this->prefetchCount = $prefetchCount;
        $this->global = $global;
    }

    public function setPrefetchCount(int $count): self
    {
        $this->prefetchCount = $count;

        return $this;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function setGlobal(bool $global): self
    {
        $this->global = $global;

        return $this;
    }

    public function isGlobal(): bool
    {
        return $this->global;
    }

    public function setPrefetchSize(int $size): self
    {
        $this->prefetchSize = $size;

        return $this;
    }

    public function getPrefetchSize(): int
    {
        return $this->prefetchSize;
    }

    public function reconfigure(array $options): self
    {
        if (isset($options['prefetch_count'])) {
            $this->setPrefetchCount((int)$options['prefetch_count']);
        }

        if (isset($options['prefetch_size'])) {
            $this->setPrefetchSize((int)$options['prefetch_size']);
        }

        if (isset($options['global'])) {
            $this->setGlobal((bool)$options['global']);
        }

        return $this;
    }

    public static function make(array $options): Qos
    {
        return new static(
            $options['prefetch_size'] ?? 0,
            $options['prefetch_count'] ?? 0,
            $options['global'] ?? false
        );
    }
}
