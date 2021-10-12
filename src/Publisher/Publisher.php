<?php

namespace Anik\Amqp\Publisher;

use Anik\Amqp\Connection\Connection;
use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Exchange;

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

        if (isset($options['exchange']) && is_array($options['exchange'])) {
            $exchange->reconfigure($options['exchange']);
        }

        if ($exchange->shouldDeclare()) {
            $channel->exchange_declare(
                $exchange->getName(),
                $exchange->getType(),
                $exchange->isPassive(),
                $exchange->isDurable(),
                $exchange->isAutoDelete(),
                $exchange->isInternal(),
                $exchange->isNoWait(),
                $exchange->getArguments(),
                $exchange->getTicket()
            );
        }

        $max = 200;

        foreach ($messages as $message) {
            if (!$message instanceof Message) {
                throw new AmqpException('Message must be an instance of Anik\Amqp\Publisher\Message');
            }

            $channel->batch_basic_publish($message->prepare(), $exchange->getName(), $routingKey);

            --$max <= 0 ? $max = 200 && $channel->publish_batch() : null;
        }

        $channel->publish_batch();

        return true;
    }
}
