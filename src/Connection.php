<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Qos\Qos;
use Anik\Amqp\Queues\Queue;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

abstract class Connection
{
    /** @var AbstractConnection */
    private $connection;
    /** @var AMQPChannel */
    private $channel;

    public function __construct(AbstractConnection $connection, ?AMQPChannel $channel = null)
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

    protected function close()
    {
        if ($this->channel && $this->channel->is_open()) {
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

    public function setChannel(AMQPChannel $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getChannel(): AMQPChannel
    {
        if (!empty($this->channel) && (null !== $this->channel->getChannelId())) {
            return $this->channel;
        }

        return $this->channel = $this->connection->channel();
    }

    public function getChannelWithId(?int $channelId): AMQPChannel
    {
        return $this->connection->channel($channelId);
    }

    protected function makeOrReconfigureExchange(?Exchange $exchange, array $options = []): Exchange
    {
        if ($options) {
            return $exchange ? $exchange->reconfigure($options) : Exchange::make($options);
        }

        if (is_null($exchange)) {
            throw new AmqpException('Cannot configure exchange');
        }

        return $exchange;
    }

    protected function makeOrReconfigureQueue(?Queue $queue, array $options = []): Queue
    {
        if ($options) {
            return $queue ? $queue->reconfigure($options) : Queue::make($options);
        }

        if (is_null($queue)) {
            throw new AmqpException('Cannot configure queue');
        }

        return $queue;
    }

    protected function prepareExchange(?Exchange $exchange, array $options): Exchange
    {
        $exchange = $this->makeOrReconfigureExchange($exchange, $options);

        $exchange->shouldDeclare() ? $this->exchangeDeclare($exchange) : null;

        return $exchange;
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

    public function queueDeclare(Queue $queue): self
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

        if ('' === $queue->getName()) {
            $queue->setName($name);
        }

        return $this;
    }

    public function queueBind(
        Queue $queue,
        Exchange $exchange,
        string $bindingKey,
        array $options = []
    ): self {
        // queue binding is not permitted on the default exchange
        if ('' === $exchange->getName()) {
            return $this;
        }

        $nowait = $options['no_wait'] ?? false;
        $arguments = $options['arguments'] ?? [];
        $ticket = $options['ticket'] ?? null;

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

    public function applyQos(Qos $qos): self
    {
        $this->getChannel()->basic_qos($qos->getPrefetchSize(), $qos->getPrefetchCount(), $qos->isGlobal());

        return $this;
    }
}
