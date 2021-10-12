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

        return (new static($options['name'], $options['type']))->reconfigure($options);
    }

    public function reconfigure(array $options): self
    {
        if (isset($options['declare'])) {
            $this->setDeclare((bool)$options['declare']);
        }

        if (isset($options['passive'])) {
            $this->setPassive((bool)$options['passive']);
        }

        if (isset($options['durable'])) {
            $this->setDurable((bool)$options['durable']);
        }

        if (isset($options['auto_delete'])) {
            $this->setAutoDelete((bool)$options['auto_delete']);
        }

        if (isset($options['internal'])) {
            $this->setInternal((bool)$options['internal']);
        }

        if (isset($options['no_wait'])) {
            $this->setNoWait((bool)$options['no_wait']);
        }

        if (isset($options['arguments'])) {
            $this->setArguments((array)$options['arguments']);
        }

        if (isset($options['ticket'])) {
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

    public function isPassive(): bool
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
