<?php

namespace Anik\Amqp\Tests\Unit\Queue;

use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Queues\Queue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    public function reconfiguringQueueWithArray(): array
    {
        return [
            'all values are set to truthy' => [
                [
                    'name' => 'example.queue',
                ],
                [
                    'declare' => true,
                    'passive' => true,
                    'durable' => true,
                    'exclusive' => true,
                    'auto_delete' => true,
                    'no_wait' => true,
                    'arguments' => ['argument'],
                    'ticket' => 1,
                ],
                8,
            ],
            'all values are set to falsy' => [
                [
                    'name' => 'example.queue',
                ],
                [
                    'declare' => false,
                    'passive' => false,
                    'durable' => false,
                    'exclusive' => false,
                    'auto_delete' => false,
                    'no_wait' => false,
                    'arguments' => [],
                    'ticket' => null,
                ],
                0,
            ],
        ];
    }

    public function validQueueNames(): array
    {
        return [
            'non-zero length queue name' => ['example.queue'],
            'zero length queue name' => [''],
        ];
    }

    public function invalidDataForCreatingQueueFromArray(): array
    {
        return [
            'name key does not exist' => [[]],
        ];
    }

    /**
     * @dataProvider validQueueNames
     *
     * @param string $name
     */
    public function testCreateQueueWithNameOnly(string $name)
    {
        $queue = new Queue($name);
        $this->assertSame($name, $queue->getName());
    }

    /**
     * @dataProvider reconfiguringQueueWithArray
     *
     * @param $createData
     * @param $options
     * @param $expectedCount
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testCreateQueueFromArray($createData, $options, $expectedCount)
    {
        $queue = Queue::make(array_merge($createData, $options));

        $this->assertCount(
            $expectedCount,
            array_filter(
                [
                    $queue->shouldDeclare(),
                    $queue->isPassive(),
                    $queue->isDurable(),
                    $queue->isExclusive(),
                    $queue->isAutoDelete(),
                    $queue->isNowait(),
                    $queue->getArguments(),
                    $queue->getTicket(),
                ]
            )
        );
    }

    /**
     * @dataProvider invalidDataForCreatingQueueFromArray
     *
     * @param $data
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testCreateQueueFromArrayWithMissingRequiredKey($data)
    {
        $this->expectException(AmqpException::class);
        Queue::make($data);
    }

    /**
     * @dataProvider reconfiguringQueueWithArray
     *
     * @param $createData
     * @param $options
     * @param $expectedCount
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testQueueCanBeReconfiguredByPassingOptionsArray($createData, $options, $expectedCount)
    {
        $queue = Queue::make($createData);
        $queue->reconfigure($options);

        $this->assertCount(
            $expectedCount,
            array_filter(
                [
                    $queue->shouldDeclare(),
                    $queue->isPassive(),
                    $queue->isDurable(),
                    $queue->isExclusive(),
                    $queue->isAutoDelete(),
                    $queue->isNowait(),
                    $queue->getArguments(),
                    $queue->getTicket(),
                ]
            )
        );
    }

    /**
     * @dataProvider reconfiguringQueueWithArray
     *
     * @param $createData
     * @param $options
     * @param $expectedCount
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testQueueIsFullyConfigurableWhenCreatingWithMakeMethod($createData, $options, $expectedCount)
    {
        $queue = Queue::make(array_merge($createData, $options));

        $this->assertCount(
            $expectedCount,
            array_filter(
                [
                    $queue->shouldDeclare(),
                    $queue->isPassive(),
                    $queue->isDurable(),
                    $queue->isExclusive(),
                    $queue->isAutoDelete(),
                    $queue->isNowait(),
                    $queue->getArguments(),
                    $queue->getTicket(),
                ]
            )
        );
    }
}
