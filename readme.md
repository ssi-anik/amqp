anik/amqp
---
`anik/amqp` is a php-amqplib wrapper that eases the consumption of RabbitMQ. A painless way of using RabbitMQ. 

You can use this package with 
- [Laravel](https://github.com/laravel/laravel)
- [Lumen](https://github.com/laravel/lumen)
- [Laravel Zero](https://github.com/laravel-zero/laravel-zero)

## Requirements
This package requires the following
- PHP >= 7.0
- ext-bcmath
- ext-sockets

## Installation
Primarily the package works with Laravel, Lumen & Laravel zero. Install it via composer. 

`composer require anik/amqp`

### For Laravel 
- Add provider in your `config/app.php` - providers array.
```php
$providers = [
    /// ... 
    Anik\Amqp\ServiceProviders\AmqpServiceProvider::class,
];
```
- Add configuration file `amqp.php` in your config directory with the following command.
```php
php artisan vendor:publish -- provider=Anik\Amqp\ServiceProviders\AmqpServiceProvider
```
### For Lumen
- Add the service provider in your `bootstrap/app.php` file.
```php
$app->register(Anik\Amqp\ServiceProviders\AmqpServiceProvider::class);
```
- Add configuration `amqp.php` in your config directory by copying it from `vendor/anik/amqp/src/config/amqp.php`.

**N.B: For Lumen, you don't need to enable Facade.**

### For Laravel Zero
- Add provider in your `config/app.php` - providers array.
```php
$providers = [
    /// ... 
    Anik\Amqp\ServiceProviders\AmqpServiceProvider::class,
];
```
- Add configuration `amqp.php` in your config directory by copying it from `vendor/anik/amqp/src/config/amqp.php`.
