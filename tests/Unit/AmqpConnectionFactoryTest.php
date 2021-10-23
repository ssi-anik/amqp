<?php

namespace Anik\Amqp\Tests\Unit;

use Anik\Amqp\AmqpConnectionFactory;
use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Producer;
use PhpAmqpLib\Connection\AMQPLazySocketConnection;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;
use PHPUnit\Framework\TestCase;

class AmqpConnectionFactoryTest extends TestCase
{
    public function loginCredentials(): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => 5672,
            'user' => 'user',
            'password' => 'password',
            'vhost' => '/',
            'options' => [],
        ];
    }

    public function testMakeMethodReturnsAmqpLazySslConnectionInstanceByDefault()
    {
        $instance = AmqpConnectionFactory::make(...$this->loginCredentials());
        $this->assertInstanceOf(AMQPLazySSLConnection::class, $instance);
    }

    public function testMakeMethodCanCreateAnyAmqpConnection()
    {
        $instance = AmqpConnectionFactory::make(
            ...array_values(
                   array_merge($this->loginCredentials(), ['amqpConnectionClass' => AMQPLazySocketConnection::class])
               )
        );
        $this->assertInstanceOf(AMQPLazySocketConnection::class, $instance);
    }

    public function testMakeMethodAcceptsSubClassOfAbstractConnectionToCreateInstance()
    {
        $this->expectException(AmqpException::class);
        AmqpConnectionFactory::make(
            ...array_values(
                   array_merge($this->loginCredentials(), ['amqpConnectionClass' => Producer::class])
               )
        );
    }

    public function testMakeFromArrayMethodReturnsAmqpLazySslConnectionInstanceByDefault()
    {
        $hosts = [$this->loginCredentials()];

        $instance = AmqpConnectionFactory::makeFromArray($hosts, []);
        $this->assertInstanceOf(AMQPLazySSLConnection::class, $instance);
    }

    public function testMakeFromArrayMethodCanCreateAnyAmqpConnection()
    {
        $hosts = [$this->loginCredentials()];

        $instance = AmqpConnectionFactory::makeFromArray($hosts, [], AMQPLazySocketConnection::class);
        $this->assertInstanceOf(AMQPLazySocketConnection::class, $instance);
    }

    public function testMakeFromArrayMethodAcceptsSubClassOfAbstractConnectionToCreateInstance()
    {
        $hosts = [$this->loginCredentials()];

        $this->expectException(AmqpException::class);
        AmqpConnectionFactory::makeFromArray($hosts, [], Producer::class);
    }
}
