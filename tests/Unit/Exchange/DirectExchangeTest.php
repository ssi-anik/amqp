<?php

namespace Anik\Amqp\Tests\Unit\Exchange;

use Anik\Amqp\Exchanges\Direct;
use Anik\Amqp\Exchanges\Exchange;

class DirectExchangeTest extends ExchangeTest
{
    public function testDirectExchangeInstantiation()
    {
        $exchange = new Direct($name = 'example.direct');
        $this->assertEquals($name, $exchange->getName());
        $this->assertEquals(Exchange::TYPE_DIRECT, $exchange->getType());
    }

    public function testDirectExchangeInstantiationFromArray()
    {
        $name = 'example.direct';

        $exchange = Direct::make(['name' => $name, 'type' => Exchange::TYPE_HEADERS]);
        $this->assertEquals($name, $exchange->getName());
        $this->assertEquals(Exchange::TYPE_DIRECT, $exchange->getType());
    }

    public function testExchangeTypeCannotBeChanged()
    {
        $exchange = new Direct($name = 'example.direct');
        $exchange->setType(Exchange::TYPE_TOPIC);
        $this->assertEquals(Exchange::TYPE_DIRECT, $exchange->getType());
    }
}