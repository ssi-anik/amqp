<?php

namespace Anik\Amqp\Tests\Unit\Qos;

use Anik\Amqp\Qos\Qos;
use PHPUnit\Framework\TestCase;

class QosTest extends TestCase
{
    public function validQosValues(): array
    {
        return [
            'no value' => [[]],
            'setting all values' => [['prefetch_size' => 1, 'prefetch_count' => 1, 'global' => false]],
            'only prefetch size' => [['prefetch_size' => 1]],
            'only prefetch count' => [['prefetch_count' => 1,]],
            'only global' => [['global' => true]],
        ];
    }

    public function testConstructQosWithDefaultParameters()
    {
        $qos = new Qos();
        $this->assertEquals(0, $qos->getPrefetchSize());
        $this->assertEquals(0, $qos->getPrefetchCount());
        $this->assertEquals(false, $qos->isGlobal());
    }

    /**
     * @dataProvider validQosValues
     *
     * @param array $data
     */
    public function testQosCanBeCreatedWithArray(array $data)
    {
        $qos = Qos::make($data);

        $this->assertEquals($data['prefetch_size'] ?? 0, $qos->getPrefetchSize());
        $this->assertEquals($data['prefetch_count'] ?? 0, $qos->getPrefetchCount());
        $this->assertEquals($data['global'] ?? false, $qos->isGlobal());
    }

    /**
     * @dataProvider validQosValues
     *
     * @param array $data
     */
    public function testQosCanBeReconfiguredWithArray(array $data)
    {
        $qos = new Qos();
        $qos->reconfigure($data);

        $this->assertEquals($data['prefetch_size'] ?? 0, $qos->getPrefetchSize());
        $this->assertEquals($data['prefetch_count'] ?? 0, $qos->getPrefetchCount());
        $this->assertEquals($data['global'] ?? false, $qos->isGlobal());
    }
}
