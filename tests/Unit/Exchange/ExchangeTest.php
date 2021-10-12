<?php

namespace Anik\Amqp\Tests\Unit\Exchange;

use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Exchange;
use PHPUnit\Framework\TestCase;

class ExchangeTest extends TestCase
{
    public function validExchangeNameAndTypeProvider(): array
    {
        return [
            'valid exchange name and type' => ['example.exchange', 'direct'],
        ];
    }

    public function invalidDataForCreatingExchangeFromArray(): array
    {
        return [
            'name key does not exist' => [['type' => 'direct']],
            'type key does not exist' => [['name' => 'example.exchange']],
            'name and type keys do not exist' => [[]],
        ];
    }

    public function reconfiguringExchangeWithArray(): array
    {
        return [
            'all values are set to positive' => [
                [
                    'name' => 'example.exchange',
                    'type' => 'direct',
                ],
                [
                    'declare' => true,
                    'passive' => true,
                    'durable' => true,
                    'auto_delete' => true,
                    'internal' => true,
                    'no_wait' => true,
                    'arguments' => ['argument'],
                    'ticket' => 1,
                ],
                8,
            ],
            'all values are set to negative' => [
                [
                    'name' => 'example.exchange',
                    'type' => 'direct',
                ],
                [
                    'declare' => false,
                    'passive' => false,
                    'durable' => false,
                    'auto_delete' => false,
                    'internal' => false,
                    'no_wait' => false,
                    'arguments' => [],
                    'ticket' => null,
                ],
                0,
            ],
        ];
    }

    /**
     * @dataProvider validExchangeNameAndTypeProvider
     *
     * @param string $name
     * @param string $type
     */
    public function testCreateExchangeWithNameAndTypeOnly(string $name, string $type)
    {
        $e = new Exchange($name, $type);
        $this->assertEquals($name, $e->getName());
        $this->assertEquals($type, $e->getType());
    }

    /**
     * @dataProvider validExchangeNameAndTypeProvider
     *
     * @param string $name
     * @param string $type
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testCreateExchangeFromArray(string $name, string $type)
    {
        $e = Exchange::make(['name' => 'example.exchange', 'type' => 'direct']);
        $this->assertEquals('example.exchange', $e->getName());
        $this->assertEquals('direct', $e->getType());
    }

    /**
     * @dataProvider invalidDataForCreatingExchangeFromArray
     *
     * @param array $data
     */
    public function testCreateExchangeFromArrayWithMissingRequiredKey(array $data)
    {
        $this->expectException(AmqpException::class);
        Exchange::make($data);
    }

    /**
     * @dataProvider reconfiguringExchangeWithArray
     *
     * @param $createData
     * @param $options
     * @param $expectedCount
     *
     * @throws \Anik\Amqp\Exceptions\AmqpException
     */
    public function testExchangeCanBeReconfiguredByPassingOptionsArray($createData, $options, $expectedCount)
    {
        $exchange = Exchange::make($createData);
        $exchange->reconfigure($options);

        $this->assertCount(
            $expectedCount,
            array_filter(
                [
                    $exchange->shouldDeclare(),
                    $exchange->isPassive(),
                    $exchange->isDurable(),
                    $exchange->isAutoDelete(),
                    $exchange->isInternal(),
                    $exchange->isNoWait(),
                    $exchange->getArguments(),
                    $exchange->getTicket(),
                ]
            )
        );
    }
}
