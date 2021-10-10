<?php

namespace Anik\Amqp\Exchanges;

use Anik\Amqp\Exceptions\AmqpException;

class Exchange
{
    const TYPE_DIRECT = 'direct';
    const TYPE_TOPIC = 'topic';
    const TYPE_FANOUT = 'fanout';
    const TYPE_HEADERS = 'headers';

    protected $name;
    protected $type;
    protected $declare = false;
    protected $passive = false;
    protected $durable = true;
    protected $autoDelete = false;
    protected $internal = false;
    protected $noWait = false;
    protected $arguments = [];
    protected $ticket = null;

    public function __construct(string $name, string $type)
    {
        $this->setName($name);
        $this->setType($type);
    }

    public static function make(array $options): Exchange
    {
        if (!isset($options['name'])) {
            throw new AmqpException('Exchange name is required.');
        }

        if (!isset($options['type'])) {
            throw new AmqpException('Exchange type is required.');
        }

        return (new static($options['name'], $options['type']))->applyOptions($options);
    }

    public function applyOptions(array $options): self
    {
        if ($options['declare'] ?? false) {
            $this->setDeclare((bool)$options['declare']);
        }

        if ($options['passive'] ?? false) {
            $this->setPassive((bool)$options['passive']);
        }

        if ($options['durable'] ?? false) {
            $this->setDurable((bool)$options['durable']);
        }

        if ($options['auto_delete'] ?? false) {
            $this->setAutoDelete((bool)$options['auto_delete']);
        }

        if ($options['internal'] ?? false) {
            $this->setInternal((bool)$options['internal']);
        }

        if ($options['no_wait'] ?? false) {
            $this->setNoWait((bool)$options['no_wait']);
        }

        if ($options['arguments'] ?? false) {
            $this->setArguments((array)$options['arguments']);
        }

        if ($options['ticket'] ?? false) {
            $this->setTicket($options['ticket']);
        }

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setDeclare(bool $declare): self
    {
        $this->declare = $declare;

        return $this;
    }

    public function shouldDeclare(): bool
    {
        return $this->declare;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPassive(): bool
    {
        return $this->passive;
    }

    public function setPassive(bool $passive): self
    {
        $this->passive = $passive;

        return $this;
    }

    public function isDurable(): bool
    {
        return $this->durable;
    }

    public function setDurable(bool $durable): self
    {
        $this->durable = $durable;

        return $this;
    }

    public function isAutoDelete(): bool
    {
        return $this->autoDelete;
    }

    public function setAutoDelete(bool $autoDelete): self
    {
        $this->autoDelete = $autoDelete;

        return $this;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function setInternal(bool $internal): self
    {
        $this->internal = $internal;

        return $this;
    }

    public function isNoWait(): bool
    {
        return $this->noWait;
    }

    public function setNoWait(bool $noWait): self
    {
        $this->noWait = $noWait;

        return $this;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getTicket()
    {
        return $this->ticket;
    }

    public function setTicket($ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }
}
