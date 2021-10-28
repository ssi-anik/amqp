<?php

namespace Anik\Amqp\Tests\Unit\Exchange;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Exchanges\Headers;
use PHPUnit\Framework\TestCase;

class HeadersExchangeTest extends TestCase
{
    public function testHeadersExchangeInstantiation()
    {
        $exchange = new Headers($name = 'example.headers');
        $this->assertEquals($name, $exchange->getName());
        $this->assertEquals(Exchange::TYPE_HEADERS, $exchange->getType());
    }

    public function testHeadersExchangeInstantiationFromArray()
    {
        $name = 'example.headers';

        $exchange = Headers::make(['name' => $name,]);
        $this->assertEquals($name, $exchange->getName());
        $this->assertEquals(Exchange::TYPE_HEADERS, $exchange->getType());
    }

    public function testExchangeTypeCannotBeChanged()
    {
        $exchange = new Headers('example.headers');
        $exchange->setType(Exchange::TYPE_DIRECT);
        $this->assertEquals(Exchange::TYPE_HEADERS, $exchange->getType());
    }
}
