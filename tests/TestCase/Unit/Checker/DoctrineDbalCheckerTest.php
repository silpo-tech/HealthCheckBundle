<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Unit\Checker;

use HealthCheck\Checker\DoctrineDbalChecker;
use HealthCheck\Tests\Traits\MockTrait;
use PHPUnit\Framework\TestCase;

class DoctrineDbalCheckerTest extends TestCase
{
    use MockTrait;

    public function testOk(): void
    {
        $checker = new DoctrineDbalChecker($this->createHealthyDbalRegistry(), $this->createLogger());

        $this->assertTrue($checker->isOk());
    }

    public function testDbalOldVersionOk(): void
    {
        $checker = new DoctrineDbalChecker($this->createHealthyDbalRegistry('2.10'), $this->createLogger());

        $this->assertTrue($checker->isOk());
    }

    public function testFail(): void
    {
        $checker = new DoctrineDbalChecker(
            $this->createUnhealthyDbalRegistry(),
            $this->createLogger('error', '[health-check] doctrine_dbal failed, reason Test DB connection failed')
        );

        $this->assertFalse($checker->isOk());
    }
}
