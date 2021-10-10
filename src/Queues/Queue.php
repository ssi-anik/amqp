<?php

namespace Anik\Amqp\Queues;

use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Qos\Qos;

class Queue
{
    protected $name;
    protected $declare = false;
    protected $passive = false;
    protected $durable = true;
    protected $exclusive = false;
    protected $autoDelete = false;
    protected $noWait = false;
    protected $arguments = [];
    protected $ticket = null;
    protected $qos = null;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
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

    public function setPassive(bool $passive): self
    {
        $this->passive = $passive;

        return $this;
    }

    public function isPassive(): bool
    {
        return $this->passive;
    }

    public function setDurable(bool $durable): self
    {
        $this->durable = $durable;

        return $this;
    }

    public function isDurable(): bool
    {
        return $this->durable;
    }

    public function setExclusive(bool $exclusive): self
    {
        $this->exclusive = $exclusive;

        return $this;
    }

    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    public function setAutoDelete(bool $autoDelete): self
    {
        $this->autoDelete = $autoDelete;

        return $this;
    }

    public function isAutoDelete(): bool
    {
        return $this->autoDelete;
    }

    public function setNoWait(bool $noWait): self
    {
        $this->noWait = $noWait;

        return $this;
    }

    public function isNoWait(): bool
    {
        return $this->noWait;
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

    public function setQos(?Qos $qos): self
    {
        $this->qos = $qos;

        return $this;
    }

    public function getQos(): ?Qos
    {
        return $this->qos;
    }

    public static function make(array $options): Queue
    {
        if (!isset($options['name'])) {
            throw new AmqpException('Exchange name is required.');
        }

        return (new static($options['name']))->applyOptions($options);
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

        if ($options['exclusive'] ?? false) {
            $this->setExclusive((bool)$options['exclusive']);
        }

        if ($options['auto_delete'] ?? false) {
            $this->setAutoDelete((bool)$options['auto_delete']);
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

        if ($options['qos'] ?? false) {
            $this->setQos($options['qos']);
        }

        return $this;
    }
}
