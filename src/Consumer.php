<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use Closure;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Consumer
{
    /* @var \PhpAmqpLib\Channel\AMQPChannel */
    private $channel;

    /* @var array */
    private $queueInfo = [];

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
     * @param \Anik\Amqp\ConsumableMessage $handler
     * @param                              $bindingKey
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     * @throws \ErrorException
     */
    public function consume (ConsumableMessage $handler, $bindingKey) {
        if (!$this->channel instanceof AMQPChannel) {
            throw new AmqpException('Channel is not defined.');
        }

        $channel = $this->getChannel();

        $callback = function (AMQPMessage $msg) use ($handler) {
            $handler->setStream($msg->body)->setDeliveryInfo((new Delivery([
                'body'          => $msg->body,
                'delivery_info' => $msg->delivery_info,
            ])))->handle();
        };

        while ( $channel->is_consuming() ) {
            $channel->wait();
        }

        /* Exchange properties */
        $ep = $handler->getExchange()->getProperties();
        if (isset($ep['declare']) && $ep['declare']) {
            $channel->exchange_declare($ep['name'], $ep['type'], $ep['passive'] ?? false, $ep['durable'] ?? true, $ep['auto_delete'] ?? false, $ep['internal'] ?? false, $ep['nowait'] ?? false, new AMQPTable($ep['properties'] ?? []));
        }

        /* Queue properties */
        $qp = $handler->getQueue()->getProperties();

        if (empty($qp['name']) || (isset($qp['declare']) && $ep['declare'])) {
            $this->queueInfo = $channel->queue_declare($qp['name'], $qp['passive'] ?? false, $qp['durable'] ?? true, $qp['exclusive'] ?? true, $qp['auto_delete'] ?? false, $qp['nowait'] ?? false, new AMQPTable($qp['properties'] ?? []));
        }

        $channel->queue_bind($handler->getQueue()->getName(), $handler->getExchange()->getName(), $bindingKey);

        /* Consumer properties */
        $consProp = $handler->getConsumer()->getProperties();
        $channel->basic_consume($handler->getQueue()
                                        ->getName(), $consProp['tag'] ?? '', $consProp['no_local'] ?? false, $consProp['no_ack'] ?? false, $consProp['exclusive'] ?? false, $consProp['nowait'] ?? false, $callback, $consProp['ticket'] ?? null, $consProp['parameters'] ?? []);

        while ( $channel->is_consuming() ) {
            $channel->wait();
        }

        /*if (isset($ep['exchange_declare']) && $ep['exchange_declare']) {
            $channel->exchange_declare($ep['name'], $ep['type'], $ep['passive'] ?? false, $ep['durable'] ?? true, $ep['auto_delete'] ?? false, $ep['internal'] ?? false, $ep['nowait'] ?? false, new AMQPTable($ep['properties'] ?? []));
        }

        $max = 200;
        foreach ( $messages as $message ) {
            $channel->batch_basic_publish(new AMQPMessage($message->getStream(), $message->getProperties()), $ep['name'], $routingKey);

            --$max <= 0 ? $max = 200 && $channel->publish_batch() : null;
        }

        $channel->publish_batch();*/
    }
}
