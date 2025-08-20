<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Traits;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use MongoDB\Client;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use ArrayIterator;

trait MockTrait
{
    private function createLogger(string|null $method = null, string|null $expectedMessage = null): LoggerInterface
    {
        $loggerMock = $this->createMock(LoggerInterface::class);

        if ($method && $expectedMessage) {
            $loggerMock->expects($this->once())
                ->method($method)
                ->with(
                    $this->callback(
                        static function ($message) use ($expectedMessage): bool {
                            return str_contains($message, $expectedMessage);
                        }
                    )
                )
            ;
        }

        return $loggerMock;
    }

    private function createHealthyDbalRegistry(string|null $version = null): ManagerRegistry
    {
        $platformMock = $this->createMock(AbstractPlatform::class);
        $platformMock->method('getDummySelectSQL')->willReturn('SELECT 1');

        $driverMock = $this->createMock(Driver::class);
        $driverMock->method('getDatabasePlatform')->willReturn($platformMock);

        if (null !== $version && $version < '2.11') {
            $connectionMock = $this->getMockBuilder(OldDbalConnection::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getDriver', 'fetchColumn'])
                ->getMock();
            $connectionMock->method('fetchColumn')->willReturn(1);
        } else {
            $connectionMock = $this->createMock(Connection::class);
            $connectionMock->method('fetchOne')->willReturn(1);
        }

        $connectionMock->method('getDriver')->willReturn($driverMock);

        $registryMock = $this->createMock(ManagerRegistry::class);
        $registryMock->method('getConnections')->willReturn([$connectionMock]);

        return $registryMock;
    }

    private function createUnhealthyDbalRegistry(): ManagerRegistry
    {
        $platformMock = $this->createMock(AbstractPlatform::class);
        $platformMock->expects($this->once())
            ->method('getDummySelectSQL')
            ->willReturn('SELECT 1')
        ;

        $driverMock = $this->createMock(Driver::class);
        $driverMock->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platformMock)
        ;

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once())
            ->method('getDriver')
            ->willReturn($driverMock)
        ;
        $connectionMock->expects($this->once())
            ->method('fetchOne')
            ->willThrowException(new RuntimeException('Test DB connection failed'))
        ;

        $registryMock = $this->createMock(ManagerRegistry::class);
        $registryMock->expects($this->once())
            ->method('getConnections')
            ->willReturn([$connectionMock])
        ;

        return $registryMock;
    }

    private function createUnhealthyMongodbRegistry(): ManagerRegistry
    {
        $connectionMock = $this->createMock(Client::class);
        $connectionMock
            ->expects($this->once())
            ->method('listDatabases')
            ->willThrowException(new RuntimeException('MongoDB connection failed'))
        ;

        $registryMock = $this->createMock(ManagerRegistry::class);
        $registryMock->expects($this->once())
            ->method('getConnections')
            ->willReturn([$connectionMock])
        ;

        return $registryMock;
    }

    private function createHealthyMongodbRegistry(): ManagerRegistry
    {
        $connectionMock = $this->createMock(Client::class);
        $connectionMock
            ->expects($this->once())
            ->method('listDatabases')
            ->willReturn(new ArrayIterator())
        ;

        $registryMock = $this->createMock(ManagerRegistry::class);
        $registryMock->expects($this->once())
            ->method('getConnections')
            ->willReturn([$connectionMock])
        ;

        return $registryMock;
    }
}

class OldDbalConnection extends Connection
{
    public function fetchColumn()
    {
    }
}