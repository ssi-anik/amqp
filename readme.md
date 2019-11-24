anik/amqp
---
`anik/amqp` is a php-amqplib wrapper that eases the consumption of RabbitMQ. A painless way of using RabbitMQ. 

You can use this package with 
- [Laravel](https://github.com/laravel/laravel)
- [Lumen](https://github.com/laravel/lumen)
- [Laravel Zero](https://github.com/laravel-zero/laravel-zero)

## Requirements
This package requires the following
- php >= 7.0
- ext-bcmath
- ext-sockets

## Installation
The package works with Laravel, Lumen & Laravel zero. Install it via composer. 

> `composer require anik/amqp`

### For Laravel 
The service provider will automatically get registered. Or you may manually add the service provider in your `config/app.php` providers array:

```php
'providers' => [
    /// ... 
    Anik\Amqp\ServiceProviders\AmqpServiceProvider::class,
]
```
- Add configuration file `amqp.php` in your config directory with the following command.
```php
php artisan vendor:publish --provider=Anik\Amqp\ServiceProviders\AmqpServiceProvider
```
### For Lumen
- Add the service provider in your `bootstrap/app.php` file.
```php
$app->register(Anik\Amqp\ServiceProviders\AmqpServiceProvider::class);
```
- Add configuration `amqp.php` in your config directory by copying it from `vendor/anik/amqp/src/config/amqp.php`. Don't forget to add `$app->configure(‘amqp’);` to your `bootstrap/app.php`.

N.B: **For Lumen, you don't need to enable Facade.**

### For Laravel Zero
- Add provider in your `config/app.php` providers array.
```php
'providers' => [
    /// ... 
    Anik\Amqp\ServiceProviders\AmqpServiceProvider::class,
]
```
- Add configuration `amqp.php` in your config directory by copying it from `vendor/anik/amqp/src/config/amqp.php`.

## Usage
- To Publish a message 
```php
<?php
// AmqpManager::publish($msg, $routing, $config);
app('amqp')->publish('Message to direct exchange', 'routing-key', [
    'exchange' => [
        'type'    => 'direct',
        'name'    => 'direct.exchange',
    ],
]);
```

- To consume a message
```php
<?php
use Anik\Amqp\ConsumableMessage;

// AmqpManager::consume($consumerHandler, $bindingKey, $config);
app('amqp')->consume(function (ConsumableMessage $message) {
    echo $message->getStream() . PHP_EOL;
    $message->getDeliveryInfo()->acknowledge();
}, 'routing-key', [
    'connection' => 'my-connection-name',
    'exchange'   => [
        'type'    => 'direct',
        'name'    => 'direct.exchange',
    ],
    'queue' => [
        'name'         => 'direct.exchange.queue',
        'declare'      => true,
        'exclusive'    => false,
    ],
    'qos' => [
        'enabled'            => true,
        'qos_prefetch_count' => 5,
    ],
]);
```

## Documentation
The full documentation of this package is written in [this article](https://medium.com/@sirajul.anik/rabbitmq-for-php-developers-c17cd019a90)

## Issues & PR

> To err is human.

- If the package generates any issue, please report it. Mention procedures to reproduce it.
- I would like to merge your PRs if they enrich the package or solve any existing issue.
