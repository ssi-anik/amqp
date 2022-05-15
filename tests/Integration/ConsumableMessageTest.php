<?php

namespace Anik\Amqp\Tests\Integration;

use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Exceptions\AmqpException;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumableMessageTest extends AmqpTestCase
{
    protected function getAmqpMessage($body = 'message'): AMQPMessage
    {
        return $this->getMockBuilder(AMQPMessage::class)->setConstructorArgs([$body, []])->getMock();
    }

    public function ackMessageDataProvider(): array
    {
        return [
            'does not provide any method param' => [
                [
                    'param' => null,
                    'expectation' => false,
                ],
            ],
            'provides param as true' => [
                [
                    'param' => true,
                    'expectation' => true,
                ],
            ],
            'provides param as false' => [
                [
                    'param' => false,
                    'expectation' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider ackMessageDataProvider
     *
     * @param array $data
     */
    public function testConsumableMessageIsAbleToPerformAck(array $data)
    {
        $expectation = $data['expectation'];
        $amqpMessage = $this->getAmqpMessage();
        $amqpMessage->expects($this->once())->method('ack')->will(
            $this->returnCallback(
                function ($multiple) use ($expectation) {
                    $this->assertSame($expectation, $multiple);
                }
            )
        );
        $param = $data['param'] ?? null;
        (new ConsumableMessage(
            function (ConsumableMessage $message) use ($param) {
                is_null($param) ? $message->ack() : $message->ack($param);
            }
        ))->setMessage($amqpMessage)->handle();
    }

    public function nackMessageDataProvider(): array
    {
        return [
            'does not provide any method param' => [
                [
                    'params' => [],
                    'expectation' => ['requeue' => false, 'multiple' => false],
                ],
            ],
            'with requeue:false' => [
                [
                    'params' => ['requeue' => false],
                    'expectation' => ['requeue' => false, 'multiple' => false],
                ],
            ],
            'with requeue:true' => [
                [
                    'params' => ['requeue' => true],
                    'expectation' => ['requeue' => true, 'multiple' => false],
                ],
            ],
            'with multiple:false' => [
                [
                    'params' => ['multiple' => false],
                    'expectation' => ['requeue' => false, 'multiple' => false],
                ],
            ],
            'with multiple:true' => [
                [
                    'params' => ['multiple' => true],
                    'expectation' => ['requeue' => false, 'multiple' => true],
                ],
            ],
            'with requeue:false / multiple:false' => [
                [
                    'params' => ['requeue' => false, 'multiple' => false],
                    'expectation' => ['requeue' => false, 'multiple' => false],
                ],
            ],
            'with requeue:false / multiple: true' => [
                [
                    'params' => ['requeue' => false, 'multiple' => true],
                    'expectation' => ['requeue' => false, 'multiple' => true],
                ],
            ],
            'with requeue:true / multiple:false' => [
                [
                    'params' => ['requeue' => true, 'multiple' => false],
                    'expectation' => ['requeue' => true, 'multiple' => false],
                ],
            ],
            'with requeue:true / multiple: true' => [
                [
                    'params' => ['requeue' => true, 'multiple' => true],
                    'expectation' => ['requeue' => true, 'multiple' => true],
                ],
            ],
        ];
    }

    /**
     * @dataProvider nackMessageDataProvider
     *
     * @param array $data
     */
    public function testConsumableMessageIsAbleToPerformNack(array $data)
    {
        $expectedRequeue = $data['expectation']['requeue'];
        $expectedMultiple = $data['expectation']['multiple'];

        $amqpMessage = $this->getAmqpMessage();
        $amqpMessage->expects($this->once())->method('nack')->will(
            $this->returnCallback(
                function ($requeue, $multiple) use ($expectedMultiple, $expectedRequeue) {
                    $this->assertSame($expectedRequeue, $requeue);
                    $this->assertSame($expectedMultiple, $multiple);
                }
            )
        );
        $params = ['requeue' => $data['params']['requeue'] ?? false];

        if (isset($data['params']['multiple'])) {
            $params['multiple'] = $data['params']['multiple'];
        }
        (new ConsumableMessage(
            function (ConsumableMessage $message) use ($params) {
                $message->nack(...array_values($params));
            }
        ))->setMessage($amqpMessage)->handle();
    }

    public function rejectMessageDataProvider(): array
    {
        return [
            'does not provide any method param' => [
                [
                    'param' => null,
                    'expectation' => true,
                ],
            ],
            'provides param as true' => [
                [
                    'param' => true,
                    'expectation' => true,
                ],
            ],
            'provides param as false' => [
                [
                    'param' => false,
                    'expectation' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider rejectMessageDataProvider
     *
     * @param array $data
     */
    public function testConsumableMessageIsAbleToPerformReject(array $data)
    {
        $expectation = $data['expectation'];
        $amqpMessage = $this->getAmqpMessage();
        $amqpMessage->expects($this->once())->method('reject')->will(
            $this->returnCallback(
                function ($reject) use ($expectation) {
                    $this->assertSame($expectation, $reject);
                }
            )
        );
        $param = $data['param'] ?? null;
        (new ConsumableMessage(
            function (ConsumableMessage $message) use ($param) {
                is_null($param) ? $message->reject() : $message->reject($param);
            }
        ))->setMessage($amqpMessage)->handle();
    }

    public function testConsumableMessageIsAbleToExtractMessageBody()
    {
        $amqpMessage = new AMQPMessage('anik.amqp.message');
        (new ConsumableMessage(
            function (ConsumableMessage $message) {
                $this->assertSame('anik.amqp.message', $message->getMessageBody());
            }
        ))->setMessage($amqpMessage)->handle();
    }

    public function testConsumableMessageHandlerPassesAmqpMessageAsSecondArgumentToCallable()
    {
        $amqpMessage = new AMQPMessage('anik.amqp.message');
        (new ConsumableMessage(
            function (ConsumableMessage $message, AMQPMessage $original) {
                $this->assertInstanceOf(ConsumableMessage::class, $message);
                $this->assertInstanceOf(AMQPMessage::class, $original);
            }
        ))->setMessage($amqpMessage)->handle();
    }

    public function testConsumableMessageCanRetrieveRoutingKey()
    {
        $amqpMessage = $this->getAmqpMessage();
        $amqpMessage->expects($this->once())->method('getRoutingKey')->willReturn('test.routing_key');
        (new ConsumableMessage(
            function (ConsumableMessage $message) {
                $message->getRoutingKey();
            }
        ))->setMessage($amqpMessage)->handle();
    }

    public function consumableMessageMethodsProvider(): array
    {
        return [
            'ack' => ['ack'],
            'nack' => ['nack'],
            'reject' => ['reject'],
            'getMessageBody' => ['getMessageBody'],
            'handle' => ['handle'],
            'getRoutingKey' => ['getRoutingKey'],
        ];
    }

    /**
     * @dataProvider consumableMessageMethodsProvider
     *
     * @param string $method
     */
    public function testCallingMethodsWithoutSettingAmqpMessageShouldThrowException(string $method)
    {
        $this->expectException(AmqpException::class);
        (new ConsumableMessage(
            function () {
            }
        ))->$method();
    }
}
