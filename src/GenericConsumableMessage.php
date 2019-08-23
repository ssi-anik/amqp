<?php

namespace Anik\Amqp;

class GenericConsumableMessage extends ConsumableMessage
{
    private $handler;

    public function __construct ($handler) {
        parent::__construct();
        $this->handler = $handler;
    }

    public function handle () {
        echo $this->getStream() . PHP_EOL;
        $this->getDeliveryInfo()->acknowledge();
    }
}
