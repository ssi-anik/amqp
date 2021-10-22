<?php

namespace Anik\Amqp\Connection;

use PhpAmqpLib\Connection\AbstractConnection;

interface ConnectionInterface
{
    public function getConnection(): AbstractConnection;

    public function getChannel(?int $channelId): ChannelInterface;

    public function close(): void;
}
