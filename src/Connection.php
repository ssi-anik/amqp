<?php

namespace Anik\Amqp;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Queues\Queue;
use Exception;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Connection\AbstractConnection;

class Connection
{
    /** @var AbstractConnection */
    private $connection;
    /** @var AbstractChannel */
    private $channel;

    public function __construct(AbstractConnection $connection, ?AbstractChannel $channel = null)
    {
        $this->connection = $connection;
        $this->channel = $channel;

        if ($connection->connectOnConstruct()) {
            $this->getChannel();
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        if ($this->channel) {
            try {
                $this->channel->close();
            } catch (Exception $e) {
            }
        }

        if ($this->connection && $this->connection->isConnected()) {
            try {
                $this->connection->close();
            } catch (Exception $e) {
            }
        }
    }

    public function setChannel(AbstractChannel $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getChannel(?int $channelId = null): AbstractChannel
    {
        // Channel is cached in the AMQP Connection
        if (!empty($this->channel) && ($channelId === $this->channel->getChannelId())) {
            return $this->channel;
        }

        return $this->channel = $this->connection->channel($channelId);
    }

    public function exchangeDeclare(Exchange $exchange): self
    {
        $this->getChannel()->exchange_declare(
            $exchange->getName(),
            $exchange->getType(),
            $exchange->isPassive(),
            $exchange->isDurable(),
            $exchange->isAutoDelete(),
            $exchange->isInternal(),
            $exchange->isNowait(),
            $exchange->getArguments(),
            $exchange->getTicket()
        );

        return $this;
    }

    public function queueDeclare(Queue $queue)
    {
        [$name,] = $this->getChannel()->queue_declare(
            $queue->getName(),
            $queue->isPassive(),
            $queue->isDurable(),
            $queue->isExclusive(),
            $queue->isAutoDelete(),
            $queue->isNowait(),
            $queue->getArguments(),
            $queue->getTicket()
        );

        if ($name !== $queue->getName()) {
            $queue->setName($name);
        }

        return $this;
    }

    public function queueBind(
        Queue $queue,
        Exchange $exchange,
        string $bindingKey,
        bool $nowait = false,
        array $arguments = [],
        ?int $ticket = null
    ): self {
        // queue binding is not permitted on the default exchange
        if ('' === $exchange->getName()) {
            return $this;
        }

        $this->getChannel()->queue_bind(
            $queue->getName(),
            $exchange->getName(),
            $bindingKey,
            $nowait,
            $arguments,
            $ticket
        );

        return $this;
    }
}
