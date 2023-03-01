<?php

namespace Anik\Amqp;

use PhpAmqpLib\Connection\AMQPConnectionConfig;

interface ConfigBuilder
{
    public function build(array $options): AMQPConnectionConfig;
}
