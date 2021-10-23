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
        $this->assertEquals($name, $exchange->getName());
        $this->assertEquals(Exchange::TYPE_TOPIC, $exchange->getType());
    }

    public function testTopicExchangeInstantiationFromArray()
    {
        $name = 'example.topic';

        $exchange = Topic::make(['name' => $name, 'type' => Exchange::TYPE_FANOUT]);
        $this->assertEquals($name, $exchange->getName());
        $this->assertEquals(Exchange::TYPE_TOPIC, $exchange->getType());
    }

    public function testExchangeTypeCannotBeChanged()
    {
        $exchange = new Topic('example.topic');
        $exchange->setType(Exchange::TYPE_FANOUT);
        $this->assertEquals(Exchange::TYPE_TOPIC, $exchange->getType());
    }
}
