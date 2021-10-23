<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Exchange;

class Producer extends Connection
{
    protected function prepareExchange(?Exchange $exchange, array $options): Exchange
    {
        $exchange = $this->makeOrReconfigureExchange($exchange, $options['exchange'] ?? []);

        $exchange->shouldDeclare() ? $this->exchangeDeclare($exchange) : null;

        return $exchange;
    }

    public function publish(
        Producible $message,
        string $routingKey = '',
        ?Exchange $exchange = null,
        array $options = []
    ) {
        $this->publishBulk([$message], $routingKey, $exchange, $options);
    }

    public function publishBulk(
        array $messages,
        string $routingKey = '',
        ?Exchange $exchange = null,
        array $options = []
    ): bool {
        if (count($messages) === 0) {
            return false;
        }

        $exchange = $this->prepareExchange($exchange, $options);

        $channel = $this->getChannel();
        $mandatory = $options['message']['mandatory'] ?? false;
        $immediate = $options['message']['immediate'] ?? false;
        $ticket = $options['message']['ticket'] ?? null;

        $count = (int)($options['bulk_count'] ?? 500);
        foreach ($messages as $message) {
            if (!$message instanceof Producible) {
                throw new AmqpException('Message must be an implementation of Anik\Amqp\Producible');
            }

            $channel->batch_basic_publish(
                $message->build(),
                $exchange->getName(),
                $routingKey,
                $mandatory,
                $immediate,
                $ticket
            );

            --$count <= 0 ? $count = 200 && $channel->publish_batch() : null;
        }

        $channel->publish_batch();

        return true;
    }

    public function publishBasic(
        Producible $message,
        string $routingKey = '',
        ?Exchange $exchange = null,
        array $options = []
    ): bool {
        $exchange = $this->prepareExchange($exchange, $options);

        $channel = $this->getChannel();
        $mandatory = $options['message']['mandatory'] ?? false;
        $immediate = $options['message']['immediate'] ?? false;
        $ticket = $options['message']['ticket'] ?? null;

        $channel->basic_publish(
            $message->build(),
            $exchange->getName(),
            $routingKey,
            $mandatory,
            $immediate,
            $ticket
        );

        return true;
    }
}
