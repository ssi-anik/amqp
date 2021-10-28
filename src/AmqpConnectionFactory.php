<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;

class AmqpConnectionFactory
{
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
        $amqpConnectionClass = $amqpConnectionClass ?? AMQPLazySSLConnection::class;
        if (!is_subclass_of($amqpConnectionClass, AbstractConnection::class)) {
            throw new AmqpException('$amqpConnectionClass expects a classname that extends AbstractConnection::class');
        }

        return $amqpConnectionClass::create_connection($hosts, $options);
    }
}
