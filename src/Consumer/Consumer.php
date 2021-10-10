<?php

namespace Anik\Amqp\Consumer;

use Anik\Amqp\Connection;
use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Queues\Queue;
use Throwable;

class Consumer
{
    public function consume(
        Connection $connection,
        Exchange $exchange,
        Queue $queue,
        Message $message,
        $bindingKey = '',
        array $options = []
    ) {
        $channelId = $options['channel_id'] ?? null;
        $channel = $connection->getChannel($channelId);

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

        if ($queue->shouldDeclare()) {
            $channel->queue_declare(
                $queue->getName(),
                $queue->isPassive(),
                $queue->isDurable(),
                $queue->isExclusive(),
                $queue->isAutoDelete(),
                $queue->isNoWait(),
                $queue->getArguments(),
                $queue->getTicket()
            );
        }

        $channel->queue_bind($queue->getName(), $exchange->getName(), $bindingKey);
        if (!is_null($qos = $queue->getQos())) {
            $channel->basic_qos($qos->getPrefetchSize(), $qos->getPrefetchCount(), $qos->isGlobal());
        }

        $channel->basic_consume(
            $queue->getName(),
            $message->getConsumerTag(),
            $message->isNoWait(),
            $message->shouldAcknowledge(),
            $message->isExclusive(),
            $message->isNoWait(),
            function ($data) use ($message) {
                $message->handle($data);
            },
            $message->getTicket(),
            $message->getArguments()
        );

        while ($channel->is_consuming()) {
            try {
                $channel->wait();
            } catch (Throwable $t) {
                throw new AmqpException(sprintf('Exception occurred. "%s"', $t->getMessage()), $t);
            }
        }
    }
}
