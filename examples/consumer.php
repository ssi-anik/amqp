<?php

require '../vendor/autoload.php';

use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Consumer;
use Anik\Amqp\Exchanges\Fanout;
use Anik\Amqp\Queues\Queue;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;

$host = '127.0.0.1';
$port = 5672;
$user = 'user';
$password = 'password';

$amqpConnection = new AMQPLazySSLConnection($host, $port, $user, $password);
$exchange = (new Fanout('example.fanout'))->setDeclare(true);
$queue = (new Queue('example.fanout.queue'))->setDeclare(true);
$bindingKey = '';

$message = new ConsumableMessage();
$status = (new Consumer($amqpConnection))->consume($message, $bindingKey, $exchange, $queue);
var_dump($status);
