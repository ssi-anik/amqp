<?php

namespace Anik\Amqp\Consumer;

class Message
{
    public function getConsumerTag(): string
    {
        return '';
    }

    public function isNoLocal(): bool
    {
        return false;
    }

    public function shouldAcknowledge(): bool
    {
        return false;
    }

    public function isExclusive(): bool
    {
        return false;
    }

    public function isNoWait(): bool
    {
        return false;
    }

    public function getTicket()
    {
        return null;
    }

    public function getArguments(): array
    {
        return [];
    }

    public function message()
    {
    }

    public function handle($data)
    {
        var_dump('data being handled by ::handle');
    }
}
