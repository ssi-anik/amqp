<?php

namespace Anik\Amqp\Connection;

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

    public function close(): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->close();
        $this->channel = null;
    }

    public function declareExchange($name, $type, $passive, $durable, $autoDelete, $internal, $arguments, $ticket)
    {
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
}
