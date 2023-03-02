<?php

namespace Anik\Amqp\Tests\Unit;

use Anik\Amqp\AmqpConnectionFactory;
use Anik\Amqp\ConfigBuilder;
use Anik\Amqp\Exceptions\AmqpException;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Connection\AMQPLazySocketConnection;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;

class AmqpConnectionFactoryTest extends TestCase
{
    public function makeMethodDataProvider(): array
    {
        $parameters = [
            'host' => '127.0.0.1',
            'port' => 5672,
            'user' => 'user',
            'password' => 'password',
            'vhost' => '/',
            'options' => [],
        ];

        return [
            'socket connection is returned if option does not specify io type' => [
                $parameters,
                AMQPSocketConnection::class,
            ],
            'checks io type from options array to make connection' => [
                array_merge_recursive($parameters, ['options' => ['io' => 'stream', 'lazy' => true]]),
                AMQPStreamConnection::class,
            ],
            'considers class name from method param to build io type' => [
                array_merge($parameters, ['classname' => AMQPLazySocketConnection::class]),
                AMQPSocketConnection::class,
            ],
            'throws exception if cannot make a connection' => [
                // Will fail for sure, because no active RabbitMQ service running and is not lazy
                array_merge($parameters, ['classname' => AMQPStreamConnection::class]),
                null,
                AmqpException::class,
            ],
        ];
    }

    public function makeFromArrayMethodDataProvider(): array
    {
        $parameters = [
            'hosts' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 5672,
                    'user' => 'user',
                    'password' => 'password',
                    'vhost' => '/',
                    'options' => [],
                ],
            ],
            'options' => [],
        ];

        return [
            'socket connection is returned if option does not specify io type' => [
                $parameters,
                AMQPSocketConnection::class,
            ],
            'checks io type from options array to make connection' => [
                array_merge_recursive($parameters, ['options' => ['io' => 'stream', 'lazy' => true]]),
                AMQPStreamConnection::class,
            ],
            'considers class name from method param to build io type' => [
                array_merge($parameters, ['classname' => AMQPLazyConnection::class]),
                AMQPStreamConnection::class,
            ],
            'throws exception if cannot make a connection' => [
                // Will fail for sure, because no active RabbitMQ service running and is not lazy
                array_merge($parameters, ['classname' => AMQPStreamConnection::class]),
                null,
                AmqpException::class,
            ],
            'throws exception when hosts array is empty' => [
                ['hosts' => [], 'options' => ['io' => 'socket', 'lazy' => true]],
                null,
                ['class' => AmqpException::class, 'message' => 'Hosts cannot be empty.'],
            ],
        ];
    }

    /** @dataProvider makeMethodDataProvider */
    public function testMakeMethod($parameters, $expected = null, $exception = null)
    {
        if ($exception) {
            $this->expectException(AmqpException::class);
        }

        $connection = AmqpConnectionFactory::make(
            $parameters['host'],
            $parameters['port'],
            $parameters['user'],
            $parameters['password'],
            $parameters['vhost'],
            $parameters['options'],
            $parameters['classname'] ?? null
        );

        if ($expected) {
            $this->assertInstanceOf($expected, $connection);
        }
    }

    /** @dataProvider makeFromArrayMethodDataProvider */
    public function testMakeFromArrayMethod($parameters, $expected = null, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception['class'] ?? $exception);

            if ($exception['message'] ?? null) {
                $this->expectExceptionMessage($exception['message']);
            }
        }

        $connection = AmqpConnectionFactory::makeFromArray(
            $parameters['hosts'],
            $parameters['options'],
            $parameters['classname'] ?? null
        );

        if ($expected) {
            $this->assertInstanceOf($expected, $connection);
        }
    }

    public function testFactoryUsesCustomBuilderIfProvided()
    {
        $configBuilder = $this->getMockBuilder(ConfigBuilder::class)
                              ->disableOriginalConstructor()
                              ->getMock();

        $configBuilder->expects($this->exactly(2))->method('build')->willReturnCallback(function() {
            $config = new AMQPConnectionConfig();
            // sets lazy connection so that doesn't try to connect to the service
            $config->setIsLazy(true);
            return $config;
        });

        AmqpConnectionFactory::builder($configBuilder);
        $host = [
            'host' => '127.0.0.1',
            'port' => 5672,
            'user' => 'user',
            'password' => 'password',
            'vhost' => '/',
        ];

        $this->assertInstanceOf(
            AbstractConnection::class,
            AmqpConnectionFactory::make($host['host'], $host['port'], $host['user'], $host['password'], $host['vhost'])
        );

        $this->assertInstanceOf(
            AbstractConnection::class,
            AmqpConnectionFactory::makeFromArray([$host])
        );
    }
}
