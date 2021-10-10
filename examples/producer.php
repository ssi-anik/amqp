<?php

require '../vendor/autoload.php';

use Anik\Amqp\Connection;
use Anik\Amqp\Exchanges\Fanout;
use Anik\Amqp\Messages\Publish\Message;
use Anik\Amqp\Publisher;

$host = '127.0.0.1';
$port = 5672;
$username = 'user';
$password = 'password';

$connection = new Connection($host, $port, $username, $password);
// $exchange = Exchange::make(['name' => 'example.direct', 'type' => 'direct', 'declare' => true]);
$exchange = (new Fanout('example.fanout'))->setDeclare(true);
$message = new Message('my message');

$status = (new Publisher())->publish($connection, $exchange, $message);
var_dump($status);
