anik/amqp
[![codecov](https://codecov.io/gh/ssi-anik/amqp/branch/master/graph/badge.svg?token=M88JATDUHN)](https://codecov.io/gh/ssi-anik/amqp)
[![PHP Version Require](http://poser.pugx.org/anik/amqp/require/php)](//packagist.org/packages/anik/amqp)
[![Total Downloads](http://poser.pugx.org/anik/amqp/downloads)](//packagist.org/packages/anik/amqp)
[![Latest Stable Version](http://poser.pugx.org/anik/amqp/v)](//packagist.org/packages/anik/amqp)
===
`anik/amqp` is a php-amqplib wrapper that eases the consumption of RabbitMQ. A painless way of using RabbitMQ.

# Note

Previously, the package could be used with Laravel, Laravel Zero, Lumen out of the box. From `v2`, the Laravel support
has been removed. If you are looking for implementation with Laravel, you can
use [anik/laravel-amqp](https://github.com/ssi-anik/laravel-amqp). If you were using this package with Laravel, and you want to upgrade to **Laravel 9**, please consider using [anik/amqp-to-laravel-amqp](https://github.com/ssi-anik/amqp-to-laravel-amqp) if you want to migrate to `anik/laravel-amqp` later.

# Examples

Checkout the [repository](https://github.com/ssi-anik/laravel-rabbitmq-producer-consumer-example) for example.

# Requirements

- PHP `^7.2 | ^8.0`
- PHP-AMQPLib `^3.0`

# Installation

To install the package, run
> `composer require anik/amqp`

# Documentation

For V1: https://medium.com/@sirajul.anik/rabbitmq-for-php-developers-c17cd019a90

## Connection

To create an AMQP Connection, you can use

- `Anik\Amqp\AmqpConnectionFactory::make`
- `Anik\Amqp\AmqpConnectionFactory::makeFromArray`

```php
<?php

use Anik\Amqp\AmqpConnectionFactory;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;

$host = '127.0.0.1';
$port = 5672;
$user = 'user';
$password = 'password';
$vhost = '/';
$options = []; // options to be proxied to the amqp connection class
$ofClass = AMQPLazySSLConnection::class;

$connection = AmqpConnectionFactory::make($host, $port, $user, $password, $vhost, $options, $ofClass);
$hosts = [
    [
        'host' => $host,
        'port' => $port,
        'user' => $user,
        'password' => $password,
        'vhost' => $vhost,
    ],
    [
        'host' => $host,
        'port' => $port,
        'user' => $user,
        'password' => $password,
        'vhost' => $vhost,
    ]
];

// With AmqpConnectionFactory::makeFromArray method, you can try to connect to multiple host
$connection = AmqpConnectionFactory::makeFromArray($hosts, $options, $ofClass);
```

## Exchange

Also, there are four specific exchange classes.

- `Anik\Amqp\Exchanges\Direct` for **direct** exchange.
- `Anik\Amqp\Exchanges\Fanout` for **fanout** exchange.
- `Anik\Amqp\Exchanges\Headers` for **headers** exchange.
- `Anik\Amqp\Exchanges\Topic` for **topic** exchange.

You can still use `Anik\Amqp\Exchanges\Exchange` base class to create your own exchange.

To instantiate an exchange, you can do like

```php
<?php

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Exchanges\Fanout;
use Anik\Amqp\Exchanges\Topic;

$exchange = new Exchange('anik.amqp.direct.exchange', Exchange::TYPE_DIRECT);

$exchange = Exchange::make(['name' => 'anik.amqp.direct.exchange', 'type' => Exchange::TYPE_DIRECT]);

$exchange = new Topic('anik.amqp.topic.exchange');

$exchange = Fanout::make(['name' => 'anik.amqp.fanout.exchange']);
```

When creating an exchange instance with

- `Exchange::make` - `name` and `type` keys must be present in the given array.
- `Topic::make` `Fanout::make` `Headers::make` `Direct::make` - `name` key must be present in the given array.

`Anik\Amqp\Exchanges\Exchange` contains a few predefined exchange types, you can use them as reference.

- `TYPE_DIRECT` for **direct** type.
- `TYPE_TOPIC` for **topic** type.
- `TYPE_FANOUT` for **fanout** type.
- `TYPE_HEADERS` for **headers** type.

The `Exchange::make` method also accepts the following keys when making an exchange instance.

- `declare` Type: `bool`. Default: `false`. If you want to declare the exchange.
- `passive` Type: `bool`. Default: `false`. If the exchange is passive.
- `durable` Type: `bool`. Default: `true`. If the exchange is durable.
- `auto_delete` Type: `bool`. Default: `false`. If the exchange should auto delete.
- `internal` Type: `bool`. Default: `false`. If the exchange is internal.
- `no_wait` Type: `bool`. Default: `false`. If the client should not wait for the server's reply.
- `arguments` Type: `array`. Default: `[]`.
- `ticket` Type: `null | integer`. Default: `null`.

You can also reconfigure the exchange instance using `$exchange->reconfigure($options)`. The `$options` array accepts
the above keys as well.

Also, you can use the following methods to configure your exchange instance.

- `setName` - Accepts: `string`. The only way to change exchange name after instantiation.
- `setDeclare` - Accepts: `bool`.
- `setType` - Accepts: `bool`.
- `setPassive` - Accepts: `bool`.
- `setDurable` - Accepts: `bool`.
- `setAutoDelete` - Accepts: `bool`.
- `setInternal` - Accepts: `bool`.
- `setNowait` - Accepts: `bool`.
- `setArguments` - Accepts: `array`.
- `setTicket` - Accepts: `null | integer`.

## Queue

To instantiate a queue, you can do like

```php
<?php

use Anik\Amqp\Queues\Queue;

$queue = new Queue('anik.amqp.direct.exchange.queue');

$queue = Queue::make(['name' => 'anik.amqp.direct.exchange.queue']);
```

When creating a queue instance with

- `Queue::make` - `name` keys must be present in the given array.

The `Queue::make` method also accepts the following keys when making a queue instance.

- `declare` Type: `bool`. Default: `false`. If you want to declare the queue.
- `passive` Type: `bool`. Default: `false`. If the queue is passive.
- `durable` Type: `bool`. Default: `true`. If the queue is durable.
- `exclusive` Type: `bool`. Default: `false`. If the queue is exclusive.
- `auto_delete` Type: `bool`. Default: `false`. If the queue should auto delete.
- `no_wait` Type: `bool`. Default: `false`. If the client should not wait for the server's reply.
- `arguments` Type: `array`. Default: `[]`.
- `ticket` Type: `null | integer`. Default: `null`.

You can also reconfigure the queue instance using `$queue->reconfigure($options)`. The `$options` array accepts the
above keys as well.

Also, you can use the following methods to configure your queue instance.

- `setName` - Accepts: `string`. The only way to change queue name after instantiation.
- `setDeclare` - Accepts: `bool`.
- `setType` - Accepts: `bool`.
- `setPassive` - Accepts: `bool`.
- `setDurable` - Accepts: `bool`.
- `setExclusive` - Accepts: `bool`.
- `setAutoDelete` - Accepts: `bool`.
- `setNowait` - Accepts: `bool`.
- `setArguments` - Accepts: `array`.
- `setTicket` - Accepts: `null | integer`.

## Qos

To instantiate a Qos, you can do like

```php
<?php

use Anik\Amqp\Qos\Qos;

$prefetchSize = 0;
$prefetchCount = 0;
$global = false;

$qos = new Qos($prefetchSize, $prefetchCount, $global);

$qos = Queue::make(['prefetch_size' => $prefetchSize, 'prefetch_count' => $prefetchCount, 'global' => $global]);
```

The `Qos::make` method also accepts the following key when making a qos instance.

- `prefetch_size` Type: `int`. Default: `0`.
- `prefetch_count` Type: `int`. Default: `0`.
- `global` Type: `bool`. Default: `true`.

You can also reconfigure the qos instance using `$qos->reconfigure($options)`. The `$options` array accepts the above
keys as well.

Also, you can use the following methods to configure your qos instance.

- `setPrefetchCount` - Accepts: `int`.
- `setPrefetchSize` - Accepts: `int`.
- `setGlobal` - Accepts: `bool`.

## Publish/Produce message

To produce/publish messages, you'll need the `Anik\Amqp\Producer` instance. To instantiate the class

```php
<?php

use Anik\Amqp\Producer;

$producer = new Producer($connection, $channel);
```

The constructor accepts

- `$connection` Type: `PhpAmqpLib\Connection\AbstractConnection`. Required.
- `$channel` Type: `null | PhpAmqpLib\Channel\AMQPChannel`. Optional.

If `$channel` is not provided or **null**, class uses the channel from the `$connection`.

Once the producer class is instantiated, you can set a channel with `setChannel`. Method
accepts `PhpAmqpLib\Channel\AMQPChannel` instance.

There are three ways to publish messages

### Bulk Publish

`Producer::publishBatch` - to publish multiple messages in bulk.

```php
<?php

use Anik\Amqp\Producer;

(new Producer($connection))->publishBatch($messages, $routingKey, $exchange, $options);
``` 

- `$messages` Type: `Anik\Amqp\Producible[]`. If any of the message is not the type of `Producible` interface, it'll
  throw error.
- `$routingKey` Type: `string`. Routing key. Default `''` (empty string).
- `$exchange` Type: `null | Anik\Amqp\Exchanges\Exchange`.
- `$options` Type: `array`. Runtime configuration.
    * Key `exchange` - Accepts: `array`.
        - If you pass `null` as `$exchange`, then you must provide a valid configuration through this key to create an
          exchange under the hood. If you pass `$exchange` with Exchange instance and `$options['exchange']`, exchange
          instance will be reconfigured accordingly with the values available in `$options['exchange']`. Keys are same
          as `Exchange::make`'s `$options`.
    * Key `publish` - Accepts: `array`.
        - Key `mandatory` Default `false`.
        - Key `immediate` Default `false`.
        - Key `ticket` Default `null`.
        - Key `batch_count`. Default: `500`. To make a batch of **X** messages before publishing a batch.

### Publish

`Producer::publish` - to publish a single message. Uses `Producer::publishBatch` under the hood.

```php
<?php

use Anik\Amqp\Producer;

(new Producer($connection))->publish($message, $routingKey, $exchange, $options);
``` 

- `$message` Type: `Anik\Amqp\Producible`.
- `$routingKey` Type: `string`. Routing key. Default `''` (empty string).
- `$exchange` Type: `null | Anik\Amqp\Exchanges\Exchange`.
- `$options` Type: `array`. Runtime configuration.
    * Key `exchange` - Accepts: `array`.
        - If you pass `null` as `$exchange`, then you must provide a valid configuration through this key to create an
          exchange under the hood. If you pass `$exchange` with Exchange instance and `$options['exchange']`, exchange
          instance will be reconfigured accordingly with the values available in `$options['exchange']`. Keys are same
          as `Exchange::make`'s `$options`.
    * Key `publish` - Accepts: `array`.
        - Key `mandatory` Default `false`.
        - Key `immediate` Default `false`.
        - Key `ticket` Default `null`.

### Publish Basic

`Producer::publishBasic` - to publish a single message using `AMQPChannel::basic_publish` method.

```php
<?php

use Anik\Amqp\Producer;

(new Producer($connection))->publishBasic($message, $routingKey, $exchange, $options);
``` 

- `$message` Type: `Anik\Amqp\Producible`.
- `$routingKey` Type: `string`. Routing key. Default `''` (empty string).
- `$exchange` Type: `null | Anik\Amqp\Exchanges\Exchange`.
- `$options` Type: `array`. Runtime configuration.
    * Key `exchange` - Accepts: `array`.
        - If you pass `null` as `$exchange`, then you must provide a valid configuration through this key to create an
          exchange under the hood. If you pass `$exchange` with Exchange instance and `$options['exchange']`, exchange
          instance will be reconfigured accordingly with the values available in `$options['exchange']`. Keys are same
          as `Exchange::make`'s `$options`.
    * Key `publish` - Accepts: `array`.
        - Key `mandatory` Default `false`.
        - Key `immediate` Default `false`.
        - Key `ticket` Default `null`.

## ProducibleMessage: Implementation of Producible Interface

The package comes with `Anik\Amqp\ProducibleMessage`, a generic implementation of `Anik\Amqp\Producible` interface.

You can instantiate the class like

```php
<?php

use Anik\Amqp\ProducibleMessage;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$msg = new ProducibleMessage('take my message to rabbitmq');

$msg = new ProducibleMessage('take my message to rabbitmq', [
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
]);

$msg = (new ProducibleMessage())->setMessage('take my message to rabbitmq')->setProperties([
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    'application_headers' => new AMQPTable(['key' => 'value']),
]);
```

## Consumer

To consume messages, you'll need the `Anik\Amqp\Consumer` instance. To instantiate the class

```php
<?php

use Anik\Amqp\Consumer;

$consumer = new Consumer($connection, $channel, $options);
```

The constructor accepts

- `$connection` Type: `PhpAmqpLib\Connection\AbstractConnection`. Required.
- `$channel` Type: `null | PhpAmqpLib\Channel\AMQPChannel`. Optional.
- `$options` Type: `array`. Optional. Configurations for consumer.
    * `tag` Type: `string`. Default `sprintf("anik.amqp_consumer_%s_%s", gethostname(), getmypid())`. To set consumer
      tag.
    * `no_local` Type: `bool`. Default `false`.
    * `no_ack` Type: `bool`. Default `false`.
    * `exclusive` Type: `bool`. Default `false`.
    * `no_wait` Type: `bool`. Default `false`.
    * `arguments` Type: `bool`. Default `[]`.
    * `ticket` Type: `null | int`. Default `null`.

If `$channel` is not provided or **null**, class uses the channel from the `$connection`.

Once the consumer class is instantiated, you can access the following methods.

- `setChannel` - Accepts: `PhpAmqpLib\Channel\AMQPChannel` instance.
- `reconfigure` - Accepts: `array`. To reconfigure the instance. Valid keys are same as constructor's options keys.
- `setConsumerTag` - Accepts: `string`. Default `sprintf("anik.amqp_consumer_%s_%s", gethostname(), getmypid())`.
- `setNoLocal` - Accepts: `bool`. Default `false`.
- `setNoAck` - Accepts: `bool`. Default `false`.
- `setExclusive` - Accepts: `bool`. Default `false`.
- `setNowait` - Accepts: `bool`. Default `false`.
- `setArguments` - Accepts: `array`. Default `[]`.
- `setTicket` - Accepts: `null | int`. Default `null`.

To consume messages,

```php
<?php

use Anik\Amqp\Consumer;

(new Consumer($connection, $channel, $options))->consume($handler, $bindingKey, $exchange, $queue, $qos, $options);
```

- `$handler` Type: `Anik\Amqp\Consumable`.
- `$bindingKey` Type: `string`. Binding key. Default `''` (empty string).
- `$exchange` Type: `null | Anik\Amqp\Exchanges\Exchange`.
- `$queue` Type: `null | Anik\Amqp\Queues\Queue`.
- `$qos` Type: `null | Anik\Amqp\Qos\Qos`.
- `$options` Type: `array`. Runtime configuration.
    * `consumer` - Accepts: `array`. Keys are same as `Consumer::__construct`'s options.
    * `exchange` - Accepts: `array`. Keys are same as `Exchange::make`'s options.
        - If you pass `null` as `$exchange`, then you must provide a valid configuration through this key to create an
          exchange under the hood. If you pass `$exchange` with Exchange instance and `$options['exchange']`, exchange
          instance will be reconfigured accordingly with the values available in `$options['exchange']`.
    * `queue` - Accepts: `array`. Keys are same as `Queue::make`'s options.
        - If you pass `null` as `$queue`, then you must provide a valid configuration through this key to create a queue
          under the hood. If you pass `$queue` with Queue instance and `$options['queue']`, queue instance will be
          reconfigured accordingly with the values available in `$options['queue']`.
    * `qos` - Accepts: `array`. Keys are same as `Qos::make`'s options.
        - If you pass `$qos` with Qos instance and `$options['qos']`, qos instance will be reconfigured accordingly.
          If `$qos` is null and `$options['qos']` holds value, QoS will be applied to the channel. If `$qos` is `null`
          and `$options['qos']` is not available, **NO QoS WILL BE APPLIED TO THE CHANNEL**
    * `bind` - Accepts: `array`. For binding queue to the exchange.
        - `no_wait`. Default `false`.
        - `arguments`. Default `[]`.
        - `ticket`. Default `null`.
    * `consume` - Accepts: `array`. Following values are passed to the `AMQPChannel::wait()`.
        - `allowed_methods` Default `null`.
        - `non_blocking` Default `false`.
        - `timeout` Default `0`.

## ConsumableMessage: Implementation of Consumable Interface

The package comes with `Anik\Amqp\ConsumableMessage`, a generic implementation of `Anik\Amqp\Consumable` interface.

You can instantiate the class like

```php
<?php

use Anik\Amqp\ConsumableMessage;
// use PhpAmqpLib\Message\AMQPMessage;

$msg = new ConsumableMessage(function (ConsumableMessage $message/*, AMQPMessage $original*/) {
    echo $message->getMessageBody() . PHP_EOL;
    echo $message->getRoutingKey() . PHP_EOL;
    $message->ack();
    // Alternatively, $original->ack();

    /** 
     * Method: `decodeMessage` 
     * Returns:
     *      - `array` if message body contains valid json
     *      - `null` if json could not be decoded 
     */
    var_dump($message->decodeMessage());

    /** 
     * Method: `decodeMessageAsObject` 
     * Returns:
     *      - `\stdClass` if message body contains valid json
     *      - `null` if json could not be decoded 
     */
    var_dump($message->decodeMessageAsObject());
});
```

**NOTE**: Calling any method on `ConsumableMessage` instance without setting **AMQPMessage** will throw exception.

# Issues?

If you find any issue/bug/missing feature, please submit an issue and PRs if possible. 
