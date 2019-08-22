<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Publisher
{
    /* @var \PhpAmqpLib\Channel\AMQPChannel */
    private $channel;

    /**
     * @return \PhpAmqpLib\Channel\AMQPChannel|null
     */
    public function getChannel () : ?AMQPChannel {
        return $this->channel;
    }

    /**
     * @param \PhpAmqpLib\Channel\AMQPChannel $channel
     *
     * @return self
     */
    public function setChannel ($channel) : self {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @param \Anik\Amqp\PublishableMessage $message
     * @param                               $routingKey
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function publish (PublishableMessage $message, $routingKey) {
        $this->publishBulk([ $message ], $routingKey);
    }

    /**
     * @param \Anik\Amqp\PublishableMessage[] $messages
     * @param                                 $routingKey
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function publishBulk ($messages, $routingKey) {
        if (!$this->channel instanceof AMQPChannel) {
            throw new AmqpException('Channel is not defined.');
        }

        if (count($messages) === 0) {
            throw new AmqpException('No message to pass to exchange.');
        }

        $ep = $messages[0]->getExchange()->getProperties();

        $channel = $this->getChannel();
        $channel->exchange_declare($ep['name'], $ep['type'], $ep['passive'] ?? false, $ep['durable'] ?? true, $ep['auto_delete'] ?? false, $ep['internal'] ?? false, $ep['nowait'] ?? false, new AMQPTable($ep['properties'] ?? []));

        $max = 200;
        foreach ( $messages as $message ) {
            $channel->batch_basic_publish(new AMQPMessage($message->getStream(), $message->getProperties()), $ep['name'], $routingKey);


            --$max <= 0 ? $max = 200 && $channel->publish_batch() : null;
        }

        $channel->publish_batch();
    }
}
