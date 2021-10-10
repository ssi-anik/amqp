<?php

require '../vendor/autoload.php';

use Anik\Amqp\Connection;
use Anik\Amqp\Consumer\Consumer;
use Anik\Amqp\Consumer\Message;
use Anik\Amqp\Exchanges\Fanout;
use Anik\Amqp\Publisher;
use Anik\Amqp\Queues\Queue;

$host = '127.0.0.1';
$port = 5672;
$username = 'user';
$password = 'password';

$connection = new Connection($host, $port, $username, $password);
// $exchange = Exchange::make(['name' => 'example.direct', 'type' => 'direct', 'declare' => true]);
$exchange = (new Fanout('example.fanout'))->setDeclare(true);
$queue = (new Queue('example.fanout.queue'))->setDeclare(true);
$bindingKey = '';

$message = new Message();
$status = (new Consumer())->consume($connection, $exchange, $queue, $message, $bindingKey);
var_dump($status);
