<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;

class Delivery
{
    private $properties;

    public function __construct (array $properties) {
        $this->setProperties($properties);
    }

    /**
     * @return array
     */
    public function getProperties () : array {
        return $this->properties;
    }

    /**
     * @param array $properties
     *
     * @return \Anik\Amqp\Delivery
     */
    public function setProperties (array $properties) : self {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Acknowledge a message
     */
    public function acknowledge () {
        $props = $this->getProperties();

        if (!isset($props['delivery_info']['channel'])) {
            throw new AmqpException('Delivery info or channel is not set');
        }

        $props['delivery_info']['channel']->basic_ack($props['delivery_info']['delivery_tag']);

        if ($props['body'] === 'quit') {
            $props['delivery_info']['channel']->basic_cancel($props['delivery_info']['consumer_tag']);
        }
    }

    /**
     * Rejects message w/ requeue
     *
     * @param bool $requeue
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function reject ($requeue = false) {
        $props = $this->getProperties();

        if (!isset($props['delivery_info']['channel'])) {
            throw new AmqpException('Delivery info or channel is not set');
        }

        $props['delivery_info']['channel']->basic_reject($props['delivery_info']['delivery_tag'], $requeue);
    }
}
