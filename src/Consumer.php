<?php

namespace Anik\Amqp;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Qos\Qos;
use Anik\Amqp\Queues\Queue;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

class Consumer extends Connection
{
    protected $consumerTag;
    protected $noLocal = false;
    protected $noAck = false;
    protected $exclusive = false;
    protected $nowait = false;
    protected $arguments = [];
    protected $ticket = null;

    public function __construct(
        AbstractConnection $connection,
        ?AMQPChannel $channel = null,
        array $options = []
    ) {
        parent::__construct($connection, $channel);

        $this->setConsumerTag($this->getDefaultConsumerTag());
        $this->reconfigure($options);
    }

    public function reconfigure(array $options): self
    {
        if (isset($options['tag'])) {
            $this->setConsumerTag($options['tag']);
        }

        if (isset($options['no_local'])) {
            $this->setNoLocal((bool)$options['no_local']);
        }

        if (isset($options['no_ack'])) {
            $this->setNoAck((bool)$options['no_ack']);
        }

        if (isset($options['exclusive'])) {
            $this->setExclusive((bool)$options['exclusive']);
        }

        if (isset($options['no_wait'])) {
            $this->setNowait((bool)$options['no_wait']);
        }

        if (isset($options['arguments'])) {
            $this->setArguments((array)$options['arguments']);
        }

        if (isset($options['ticket'])) {
            $this->setTicket($options['ticket']);
        }

        return $this;
    }

    public function getConsumerTag(): string
    {
        return $this->consumerTag;
    }

    public function setConsumerTag(string $tag): self
    {
        $this->consumerTag = $tag;

        return $this;
    }

    public function isNoLocal(): bool
    {
        return $this->noLocal;
    }

    public function setNoLocal(bool $noLocal): self
    {
        $this->noLocal = $noLocal;

        return $this;
    }

    public function isNoAck(): bool
    {
        return $this->noAck;
    }

    public function setNoAck(bool $noAck): self
    {
        $this->noAck = $noAck;

        return $this;
    }

    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    public function setExclusive(bool $exclusive): self
    {
        $this->exclusive = $exclusive;

        return $this;
    }

    public function isNowait(): bool
    {
        return $this->nowait;
    }

    public function setNowait(bool $nowait): self
    {
        $this->nowait = $nowait;

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

    public function getTicket(): ?int
    {
        return $this->ticket;
    }

    public function setTicket(?int $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function consume(
        Consumable $handler,
        string $bindingKey = '',
        ?Exchange $exchange = null,
        ?Queue $queue = null,
        ?Qos $qos = null,
        array $options = []
    ) {
        if (isset($options['consumer'])) {
            $this->reconfigure($options['consumer']);
        }

        $exchange = $this->prepareExchange($exchange, $options['exchange'] ?? []);
        $queue = $this->prepareQueue($queue, $options['queue'] ?? []);

        $this->queueBind($queue, $exchange, $bindingKey, $options['bind'] ?? []);

        if ($qos = $this->prepareQos($qos, $options['qos'] ?? [])) {
            $this->applyQos($qos);
        }

        $this->getChannel()->basic_consume(
            $queue->getName(),
            $this->getConsumerTag(),
            $this->isNoLocal(),
            $this->isNoAck(),
            $this->isExclusive(),
            $this->isNowait(),
            function ($message) use ($handler) {
                $handler->setMessage($message)->handle();
            },
            $this->getTicket(),
            $this->getArguments()
        );

        $allowedMethods = $options['consume']['allowed_methods'] ?? null;
        $nonBlocking = $options['consume']['non_blocking'] ?? false;
        $timeout = $options['consume']['timeout'] ?? 0;
        while ($this->getChannel()->is_consuming()) {
            $this->getChannel()->wait($allowedMethods, $nonBlocking, $timeout);
        }
    }

    protected function getDefaultConsumerTag(): string
    {
        return sprintf("anik.amqp_consumer_%s_%s", gethostname(), getmypid());
    }

    protected function prepareQueue(?Queue $queue, array $options): Queue
    {
        $queue = $this->makeOrReconfigureQueue($queue, $options);

        $queue->shouldDeclare() ? $this->queueDeclare($queue) : null;

        return $queue;
    }

    protected function prepareQos(?Qos $qos, array $options = []): ?Qos
    {
        if ($options) {
            $qos = $qos ? $qos->reconfigure($options) : Qos::make($options);
        }

        return $qos;
    }
}
