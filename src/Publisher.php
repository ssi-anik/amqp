<?php

namespace Anik\Amqp;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Messages\Publish\Message;

class Publisher
{
    public function publish(
        Connection $connection,
        Exchange $exchange,
        Message $message,
        string $routingKey = '',
        array $options = []
    ): bool {
        return $this->publishBulk($connection, $exchange, [$message], $routingKey, $options);
    }

    public function publishBulk(
        Connection $connection,
        Exchange $exchange,
        array $messages,
        string $routingKey = '',
        array $options = []
    ): bool {
        $channelId = $options['channel_id'] ?? null;
        $channel = $connection->getChannel($channelId);

        if (count($messages) === 0) {
            return false;
        }

        if ($exchange->shouldDeclare()) {
            $channel->exchange_declare(
                $exchange->getName(),
                $exchange->getType(),
                $exchange->getPassive(),
                $exchange->isDurable(),
                $exchange->isAutoDelete(),
                $exchange->isInternal(),
                $exchange->isNoWait(),
                $exchange->getArguments(),
                $exchange->getTicket()
            );
        }

        $max = 200;

        /** @var Message $message */
        foreach ($messages as $message) {
            $channel->batch_basic_publish($message->prepare(), $exchange->getName(), $routingKey);

            --$max <= 0 ? $max = 200 && $channel->publish_batch() : null;
        }

        $channel->publish_batch();

        return true;
    }
}
