<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory as PhpAmqplibConnectionFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Throwable;

class AmqpConnectionFactory
{
    protected static $builder = null;

    public static function builder(ConfigBuilder $builder)
    {
        static::$builder = $builder;
    }

    public static function getBuilder(): ConfigBuilder
    {
        return static::$builder ?? new AmqpConnectionConfigBuilder();
    }

    public static function make(
        string $host,
        int $port,
        string $user,
        ?string $password,
        string $vhost = '/',
        array $options = [],
        ?string $amqpConnectionClass = null
    ): AbstractConnection {
        $hosts[] = [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'vhost' => $vhost,
        ];

        return static::makeFromArray($hosts, $options, $amqpConnectionClass);
    }

    public static function makeFromArray(
        array $hosts,
        array $options = [],
        ?string $amqpConnectionClass = null
    ): AbstractConnection {
        if ($amqpConnectionClass && !isset($options['io'])) {
            $options['io'] = is_a($amqpConnectionClass, AMQPStreamConnection::class, true)
                ? AMQPConnectionConfig::IO_TYPE_STREAM
                : AMQPConnectionConfig::IO_TYPE_SOCKET;
            $options['lazy'] = strpos($amqpConnectionClass, 'Lazy') !== false;
        }

        if (empty($hosts)) {
            throw new AmqpException('Hosts cannot be empty.');
        }

        $lastException = null;
        $builder = static::getBuilder();

        foreach ($hosts as $host) {
            try {
                return PhpAmqplibConnectionFactory::create($builder->build($host + $options));
            } catch (Throwable $t) {
                $lastException = $t;
            }
        }

        throw new AmqpException($lastException->getMessage());
    }
}
