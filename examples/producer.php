<?php

require '../vendor/autoload.php';

use Anik\Amqp\Connection\Connection;
use Anik\Amqp\Exchanges\Direct;
use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Exchanges\Fanout;
use Anik\Amqp\Exchanges\Headers;
use Anik\Amqp\Exchanges\Topic;
use Anik\Amqp\Producer\Message;
use Anik\Amqp\Producer\Producer;

function __getExchange($options): Exchange
{
    return Exchange::make($options);
}

function __getFanoutExchange($options): Fanout
{
    return Fanout::make($options);
}

function __getHeadersExchange($options): Headers
{
    return Headers::make($options);
}

function __getTopicExchange($options): Topic
{
    return Topic::make($options);
}

function __getDirectExchange($options): Direct
{
    return Direct::make($options);
}

function __publishToExchanges(Connection $connection, Message $message, $method = 'publish')
{
    $exchangeCreateOptions = [
        /*'name' => 'example.direct', 'type' => 'direct',*/
        'declare' => true,
    ];
    $exchangeTypes = ['', 'fanout', 'headers', 'topic', 'direct'];

    foreach ($exchangeTypes as $type) {
        $function = sprintf('__get%sExchange', ucfirst($type));
        $name = sprintf('example.%s', empty($type) ? 'direct' : $type);
        $exchangeType = empty($type) ? 'direct' : $type;

        /** @var Exchange $exchange */
        $exchange = call_user_func(
            $function,
            array_merge($exchangeCreateOptions, ['name' => $name, 'type' => $exchangeType])
        );
        $result = (new Producer())->$method($connection, $exchange, $message);

        echo sprintf(
            '[%s] [Method: %s] [Exchange: %s]: Response %s%s',
            get_class($exchange),
            $method,
            $exchange->getType(),
            $result ? 'Success' : 'Error',
            PHP_EOL
        );
    }
}

$host = '127.0.0.1';
$port = 5672;
$username = 'user';
$password = 'password';

$connection = new Connection($host, $port, $username, $password);
$message = new Message('my message');

//__publishToExchanges($connection, $message);
__publishToExchanges($connection, $message, 'publishBasic');
