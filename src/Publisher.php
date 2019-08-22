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

    public function publish (PublishableMessage $message, $routingKey) {
        if (!$this->channel instanceof AMQPChannel) {
            throw new AmqpException('Channel is not defined.');
        }

        $amqpMessage = new AMQPMessage($message->getStream(), $message->getProperties());
        $ep = $message->getExchange()->getProperties();

        $channel = $this->getChannel();
        $channel->exchange_declare($ep['name'], $ep['type'], $ep['passive'] ?? false, $ep['durable'] ?? true,
            $ep['auto_delete'] ?? false, $ep['internal'] ?? false, $ep['nowait'] ?? false,
            new AMQPTable($ep['properties'] ?? []));

        $channel->basic_publish($amqpMessage, $ep['name'], $routingKey);
    }
}
