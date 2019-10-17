<?php

if (!function_exists('consume')) {
    function consume ($closure, $bindingKey, array $config = [])
    {
        resolve('amqp')->consume($closure, $bindingKey, $config);
    }
}

if (!function_exists('publish')) {
    function publish ($message, $routingKey, array $config = [])
    {
        resolve('amqp')->publish($message, $routingKey, $config);
    }
}
