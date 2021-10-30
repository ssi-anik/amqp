<?php

namespace Anik\Amqp\Tests\Unit\Exchange;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Exchanges\Topic;
use PHPUnit\Framework\TestCase;

class TopicExchangeTest extends TestCase
{
    public function testTopicExchangeInstantiation()
    {
        $exchange = new Topic($name = 'example.topic');
        $this->assertSame($name, $exchange->getName());
        $this->assertSame(Exchange::TYPE_TOPIC, $exchange->getType());
    }

    public function testTopicExchangeInstantiationFromArray()
    {
        $name = 'example.topic';

        $exchange = Topic::make(['name' => $name,]);
        $this->assertSame($name, $exchange->getName());
        $this->assertSame(Exchange::TYPE_TOPIC, $exchange->getType());
    }

    public function testExchangeTypeCannotBeChanged()
    {
        $exchange = new Topic('example.topic');
        $exchange->setType(Exchange::TYPE_FANOUT);
        $this->assertSame(Exchange::TYPE_TOPIC, $exchange->getType());
    }
}
