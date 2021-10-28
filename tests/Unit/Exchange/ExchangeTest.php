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
            'exchange name and type set 1' => ['example.exchange', 'direct'],
            'exchange name and type set 2' => ['example.exchange', 'fanout'],
            'delayed exchange name and type' => ['example.delayed-exchange', 'x-delayed-message'],
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
            'all values are set to truthy' => [
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
            'all values are set to falsy' => [
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

    public function exchangeOptionsValueProvider(): array
    {
        return [
            'setting name' => [
                [
                    'changes' => [
                        'setName' => 'anik.amqp.exchange.new-name',
                    ],
                    'checks' => [
                        'getName' => 'anik.amqp.exchange.new-name',
                    ],
                ],
            ],
            'setting type' => [
                [
                    'changes' => [
                        'setType' => Exchange::TYPE_TOPIC,
                    ],
                    'checks' => [
                        'getType' => Exchange::TYPE_TOPIC,
                    ],
                ],
            ],
            'setting declare' => [
                [
                    'changes' => [
                        'setDeclare' => true,
                    ],
                    'checks' => [
                        'shouldDeclare' => true,
                    ],
                ],
            ],
            'setting passive' => [
                [
                    'changes' => [
                        'setPassive' => true,
                    ],
                    'checks' => [
                        'isPassive' => true,
                    ],
                ],
            ],
            'setting durable' => [
                [
                    'changes' => [
                        'setDurable' => false,
                    ],
                    'checks' => [
                        'isDurable' => false,
                    ],
                ],
            ],
            'setting auto delete' => [
                [
                    'changes' => [
                        'setAutoDelete' => true,
                    ],
                    'checks' => [
                        'isAutoDelete' => true,
                    ],
                ],
            ],
            'setting internal' => [
                [
                    'changes' => [
                        'setInternal' => true,
                    ],
                    'checks' => [
                        'isInternal' => true,
                    ],
                ],
            ],
            'setting no wait' => [
                [
                    'changes' => [
                        'setNowait' => true,
                    ],
                    'checks' => [
                        'isNowait' => true,
                    ],
                ],
            ],
            'setting arguments' => [
                [
                    'changes' => [
                        'setArguments' => ['key' => 'value'],
                    ],
                    'checks' => [
                        'getArguments' => ['key' => 'value'],
                    ],
                ],
            ],
            'setting ticket' => [
                [
                    'changes' => [
                        'setTicket' => 100,
                    ],
                    'checks' => [
                        'getTicket' => 100,
                    ],
                ],
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
        $e = Exchange::make(['name' => $name, 'type' => $type]);
        $this->assertEquals($name, $e->getName());
        $this->assertEquals($type, $e->getType());
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
                    $exchange->isNowait(),
                    $exchange->getArguments(),
                    $exchange->getTicket(),
                ]
            )
        );
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
    public function testExchangeIsFullyConfigurableWhenCreatingWithMakeMethod($createData, $options, $expectedCount)
    {
        $exchange = Exchange::make(array_merge($createData, $options));

        $this->assertCount(
            $expectedCount,
            array_filter(
                [
                    $exchange->shouldDeclare(),
                    $exchange->isPassive(),
                    $exchange->isDurable(),
                    $exchange->isAutoDelete(),
                    $exchange->isInternal(),
                    $exchange->isNowait(),
                    $exchange->getArguments(),
                    $exchange->getTicket(),
                ]
            )
        );
    }

    /**
     * @dataProvider exchangeOptionsValueProvider
     *
     * @param array $data
     */
    public function testExchangePropertiesCanBeModifiedByCallingMethods(array $data)
    {
        $exchange = new Exchange('anik.amqp.exchange.fanout', Exchange::TYPE_FANOUT);
        foreach ($data['changes'] as $method => $value) {
            $exchange->$method($value);
        }

        foreach ($data['checks'] ?? [] as $method => $value) {
            $this->assertEquals($value, $exchange->$method());
        }
    }
}
