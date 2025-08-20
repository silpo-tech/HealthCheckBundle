<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Unit\Checker;

use HealthCheck\Checker\DoctrineMongoDBChecker;
use HealthCheck\Tests\Traits\MockTrait;
use PHPUnit\Framework\TestCase;

class DoctrineMongoDBCheckerTest extends TestCase
{
    use MockTrait;

    public function testOk(): void
    {
        $checker = new DoctrineMongoDBChecker($this->createHealthyMongodbRegistry(), $this->createLogger());

        $this->assertTrue($checker->isOk());
    }

    public function testFail(): void
    {
        $checker = new DoctrineMongoDBChecker(
            $this->createUnhealthyMongodbRegistry(),
            $this->createLogger('error', '[health-check] doctrine_mongodb failed, reason MongoDB connection failed')
        );

        $this->assertFalse($checker->isOk());
    }
}
