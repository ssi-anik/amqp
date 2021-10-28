<?php

namespace Anik\Amqp\Tests\Unit\Exchange;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Exchanges\Fanout;
use PHPUnit\Framework\TestCase;

class FanoutExchangeTest extends TestCase
{
    public function testFanoutExchangeInstantiation()
    {
        $exchange = new Fanout($name = 'example.fanout');
        $this->assertEquals($name, $exchange->getName());
        $this->assertEquals(Exchange::TYPE_FANOUT, $exchange->getType());
    }

    public function testFanoutExchangeInstantiationFromArray()
    {
        $name = 'example.fanout';

        $exchange = Fanout::make(['name' => $name,]);
        $this->assertEquals($name, $exchange->getName());
        $this->assertEquals(Exchange::TYPE_FANOUT, $exchange->getType());
    }

    public function testExchangeTypeCannotBeChanged()
    {
        $exchange = new Fanout($name = 'example.fanout');
        $exchange->setType(Exchange::TYPE_TOPIC);
        $this->assertEquals(Exchange::TYPE_FANOUT, $exchange->getType());
    }
}
