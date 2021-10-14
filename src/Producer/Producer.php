<?php

namespace Anik\Amqp\Producer;

use Anik\Amqp\Contracts\ChannelInterface;
use Anik\Amqp\Contracts\ConnectionInterface;
use Anik\Amqp\Contracts\ProducerMessageInterface;
use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Exchange;

class Producer
{
    final protected function prepareBeforePublish(
        ConnectionInterface $connection,
        Exchange $exchange,
        array $options = []
    ): ChannelInterface {
        $channelId = $options['channel_id'] ?? null;
        $channel = $connection->getChannel($channelId);

        if (isset($options['exchange']) && is_array($options['exchange'])) {
            $exchange->reconfigure($options['exchange']);
        }

        $exchange->shouldDeclare() ? $exchange->declare($channel) : null;

        return $channel;
    }

    public function publish(
        ConnectionInterface $connection,
        Exchange $exchange,
        ProducerMessageInterface $message,
        string $routingKey = '',
        array $options = []
    ): bool {
        return $this->publishBulk($connection, $exchange, [$message], $routingKey, $options);
    }

    public function publishBulk(
        ConnectionInterface $connection,
        Exchange $exchange,
        array $messages,
        string $routingKey = '',
        array $options = []
    ): bool {
        if (count($messages) === 0) {
            return false;
        }

        $channel = $this->prepareBeforePublish($connection, $exchange);

        $max = 200;
        foreach ($messages as $message) {
            if (!$message instanceof ProducerMessageInterface) {
                throw new AmqpException(
                    'Message must be an implementation of Anik\Amqp\Contracts\ProducerMessageInterface'
                );
            }

            $mandatory = $options['mandatory'] ?? false;
            $immediate = $options['immediate'] ?? false;
            $ticket = $options['ticket'] ?? null;

            $channel->batchBasicPublish(
                $message->prepare(),
                $exchange->getName(),
                $routingKey,
                $mandatory,
                $immediate,
                $ticket
            );

            --$max <= 0 ? $max = 200 && $channel->publish_batch() : null;
        }

        $channel->publish_batch();

        return true;
    }

    public function publishBasic(
        ConnectionInterface $connection,
        Exchange $exchange,
        ProducerMessageInterface $message,
        string $routingKey = '',
        array $options = []
    ): bool {
        $channel = $this->prepareBeforePublish($connection, $exchange);

        $mandatory = $options['mandatory'] ?? false;
        $immediate = $options['immediate'] ?? false;
        $ticket = $options['ticket'] ?? null;

        $channel->basicPublish(
            $message->prepare(),
            $exchange->getName(),
            $routingKey,
            $mandatory,
            $immediate,
            $ticket
        );

        return true;
    }
}
