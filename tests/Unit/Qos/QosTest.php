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
        $this->assertSame(0, $qos->getPrefetchSize());
        $this->assertSame(0, $qos->getPrefetchCount());
        $this->assertSame(false, $qos->isGlobal());
    }

    /**
     * @dataProvider validQosValues
     *
     * @param array $data
     */
    public function testQosCanBeCreatedWithArray(array $data)
    {
        $qos = Qos::make($data);

        $this->assertSame($data['prefetch_size'] ?? 0, $qos->getPrefetchSize());
        $this->assertSame($data['prefetch_count'] ?? 0, $qos->getPrefetchCount());
        $this->assertSame($data['global'] ?? false, $qos->isGlobal());
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

        $this->assertSame($data['prefetch_size'] ?? 0, $qos->getPrefetchSize());
        $this->assertSame($data['prefetch_count'] ?? 0, $qos->getPrefetchCount());
        $this->assertSame($data['global'] ?? false, $qos->isGlobal());
    }
}
