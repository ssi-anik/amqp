<?php

namespace Anik\Amqp\Connection;

use Anik\Amqp\Qos\Qos;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class Channel implements ChannelInterface
{
    private $connection;
    private $channel;

    public function __construct(ConnectionInterface $connection, AMQPChannel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function getAmqpChannel(): AMQPChannel
    {
        return $this->channel;
    }

    public function getChannelId(): int
    {
        return $this->channel->getChannelId();
    }

    public function isConsuming(): bool
    {
        return $this->channel->is_consuming();
    }

    public function basicConsume(
        string $queue,
        string $consumerTag,
        bool $noLocal = false,
        bool $noAck = false,
        bool $exclusive = false,
        bool $nowait = false,
        $callback = null,
        ?int $ticket = null,
        array $arguments = []
    ): string {
        return $this->channel->basic_consume(
            $queue,
            $consumerTag,
            $noLocal,
            $noAck,
            $exclusive,
            $nowait,
            $callback,
            $ticket,
            $arguments
        );
    }

    public function wait(?array $allowedMethods = null, bool $nonBlocking = false, ?int $timeout = 0)
    {
        $this->channel->wait($allowedMethods, $nonBlocking, $timeout);
    }

    public function close(): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->close();
        $this->channel = null;
    }

    public function declareExchange(
        string $name,
        string $type,
        bool $passive,
        bool $durable,
        bool $autoDelete,
        bool $internal,
        array $arguments = [],
        ?int $ticket = null
    ) {
        return $this->channel->exchange_declare(
            $name,
            $type,
            $passive,
            $durable,
            $autoDelete,
            $internal,
            $arguments,
            $ticket
        );
    }

    public function declareQueue(
        string $queue,
        bool $passive,
        bool $durable,
        bool $exclusive,
        bool $autoDelete,
        bool $nowait,
        array $arguments = [],
        ?int $ticket = null
    ): ?array {
        return $this->channel->queue_declare(
            $queue,
            $passive,
            $durable,
            $exclusive,
            $autoDelete,
            $nowait,
            $arguments,
            $ticket
        );
    }

    public function batchBasicPublish(
        AMQPMessage $message,
        string $exchangeName = '',
        string $routingKey = '',
        bool $mandatory = false,
        bool $immediate = false,
        ?int $ticket = null
    ): void {
        $this->channel->batch_basic_publish($message, $exchangeName, $routingKey, $mandatory, $immediate, $ticket);
    }

    public function publishBatch(): void
    {
        $this->channel->publish_batch();
    }


    public function basicPublish(
        AMQPMessage $message,
        string $exchangeName = '',
        string $routingKey = '',
        bool $mandatory = false,
        bool $immediate = false,
        ?int $ticket = null
    ): void {
        $this->channel->basic_publish(
            $message,
            $exchangeName,
            $routingKey,
            $mandatory,
            $immediate,
            $ticket
        );
    }

    public function queueBind(
        string $queueName,
        string $exchangeName,
        string $bindingKey,
        bool $nowait = false,
        array $arguments = [],
        ?int $ticket = null
    ) {
        return $this->channel->queue_bind(
            $queueName,
            $exchangeName,
            $bindingKey,
            $nowait,
            $arguments,
            $ticket
        );
    }

    public function withQos(Qos $qos)
    {
        $this->channel->basic_qos($qos->getPrefetchSize(), $qos->getPrefetchCount(), $qos->isGlobal());
    }
}
