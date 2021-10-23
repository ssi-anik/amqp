<?php

namespace Anik\Amqp\Connection;

use Anik\Amqp\Qos\Qos;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

interface ChannelInterface
{
    public function getAmqpChannel(): AMQPChannel;

    public function getChannelId(): int;

    public function isConsuming(): bool;

    public function wait(?array $allowedMethods = null, bool $nonBlocking = false, ?int $timeout = 0);

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
    ): string;

    public function declareExchange(
        string $name,
        string $type,
        bool $passive,
        bool $durable,
        bool $autoDelete,
        bool $internal,
        array $arguments = [],
        ?int $ticket = null
    );

    public function declareQueue(
        string $queue,
        bool $passive,
        bool $durable,
        bool $exclusive,
        bool $autoDelete,
        bool $nowait,
        array $arguments = [],
        ?int $ticket = null
    ): ?array;

    public function close(): void;

    public function batchBasicPublish(
        AMQPMessage $message,
        string $exchangeName,
        string $routingKey,
        bool $mandatory,
        bool $immediate,
        ?int $ticket
    ): void;

    public function publishBatch(): void;

    public function basicPublish(
        AMQPMessage $message,
        string $exchangeName,
        string $routingKey,
        bool $mandatory,
        bool $immediate,
        ?int $ticket
    ): void;

    public function queueBind(
        string $queueName,
        string $exchangeName,
        string $bindingKey,
        bool $nowait = false,
        array $arguments = [],
        ?int $ticket = null
    );

    public function withQos(Qos $qos);
}
