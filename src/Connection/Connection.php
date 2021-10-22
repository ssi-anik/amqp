<?php

namespace Anik\Amqp\Connection;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;

class Connection implements ConnectionInterface
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $vhost;
    private $sslOptions;
    private $connectOptions;
    private $sslProtocol;

    /** @var AbstractConnection $connection */
    private $connection;
    /** @var ChannelInterface[] $channels */
    private $channels = [];

    public function __construct(
        string $host,
        int $port,
        string $username,
        ?string $password,
        string $vhost = '/',
        ?array $sslOptions = [],
        ?array $connectOptions = [],
        ?string $sslProtocol = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->sslOptions = $sslOptions;
        $this->connectOptions = $connectOptions;
        $this->sslProtocol = $sslProtocol;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function getConnection(): AbstractConnection
    {
        return $this->connection ?? ($this->connection = $this->getAmqpConnection());
    }

    public function getChannel(?int $channelId = null): ChannelInterface
    {
        if (!is_null($channelId) && isset($this->channels[$channelId])) {
            return $this->channels[$channelId];
        }

        $channel = $this->getConnection()->channel($channelId);

        return $this->channels[$channel->getChannelId()] = new Channel($this, $channel);
    }

    public function getAmqpConnection(): AbstractConnection
    {
        return new AMQPLazySSLConnection(
            $this->host,
            $this->port,
            $this->username,
            $this->password,
            $this->vhost,
            $this->sslOptions,
            $this->connectOptions,
            $this->sslProtocol
        );
    }

    public function close(): void
    {
        if (!$this->connection || !$this->connection->isConnected()) {
            return;
        }

        /** Make sure to close channels */
        foreach ($this->channels as $channel) {
            $channel->close();
        }

        $this->connection->close();
        $this->connection = null;
    }
}
