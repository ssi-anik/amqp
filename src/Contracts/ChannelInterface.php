<?php

namespace Anik\Amqp\Contracts;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

interface ChannelInterface
{
    public function getAmqpChannel(): AMQPChannel;

    public function getChannelId(): int;

    public function declareExchange($name, $type, $passive, $durable, $autoDelete, $internal, $arguments, $ticket);

    public function close(): void;

    public function batchBasicPublish(
        AMQPMessage $message,
        string $exchangeName,
        string $routingKey,
        bool $mandatory,
        bool $immediate,
        ?int $ticket
    ): void;

    public function basicPublish(
        AMQPMessage $message,
        string $exchangeName,
        string $routingKey,
        bool $mandatory,
        bool $immediate,
        ?int $ticket
    ): void;
}
