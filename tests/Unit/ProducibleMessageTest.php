<?php

namespace Anik\Amqp\Tests\Unit;

use Anik\Amqp\ProducibleMessage;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class ProducibleMessageTest extends TestCase
{
    protected const MESSAGE_STREAM = 'anik.amqp.msg';

    public function producibleMessageParams(): array
    {
        return [
            'only message' => [
                [
                    self::MESSAGE_STREAM,
                ],
            ],
            'message and properties' => [
                [
                    self::MESSAGE_STREAM,
                    ['delivery_mode' => AMQPMessage::DELIVERY_MODE_NON_PERSISTENT],
                ],
            ],
        ];
    }

    /**
     * @dataProvider producibleMessageParams
     *
     * @param array $data
     */
    public function testProducibleMessageInstantiation(array $data)
    {
        $constructionParams = [];
        $constructionParams[] = $msg = $data[0];
        if ($properties = $data[1] ?? []) {
            $constructionParams[] = $properties;
        }

        $producible = new ProducibleMessage(...$constructionParams);
        $this->assertSame($msg, $producible->getMessage());
        $this->assertSame($properties, $producible->getProperties());
    }

    /**
     * @dataProvider producibleMessageParams
     *
     * @param array $data
     */
    public function testProducibleMessageDataCanBeSetOutsideTheClass(array $data)
    {
        $producible = (new ProducibleMessage())->setMessage($msg = $data[0]);
        if ($properties = $data[1] ?? []) {
            $producible->setProperties($properties);
        }

        $this->assertSame($msg, $producible->getMessage());
        $this->assertSame($properties, $producible->getProperties());
    }
}
