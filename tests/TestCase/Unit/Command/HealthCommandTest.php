<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Unit\Command;

use SilpoTech\Lib\TestUtilities\TestCase\Traits\UtilityTrait;
use HealthCheck\Checker\CheckerInterface;
use HealthCheck\Checker\DoctrineDbalChecker;
use HealthCheck\Checker\DoctrineMongoDBChecker;
use HealthCheck\Command\HealthCommand;
use HealthCheck\Tests\Traits\MockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class HealthCommandTest extends TestCase
{
    use MockTrait;
    use UtilityTrait;

    /** @dataProvider executeDataProvider */
    public function testExecute(array $checkers, callable $checkerServices, array $expected): void
    {
        $checkerServices = $this->resolveValue($checkerServices);
        $commandTester = new CommandTester(new HealthCommand($checkerServices, $checkers));
        $commandTester->execute([]);

        $this->assertEquals($expected['statusCode'], $commandTester->getStatusCode());
        $this->assertEquals($expected['displayMessage'], $commandTester->getDisplay());
    }

    public static function executeDataProvider(): iterable
    {
        yield 'ok with mongodb and dbal' => [
            'checkers' => [DoctrineDbalChecker::NAME, DoctrineMongoDBChecker::NAME],
            'checkerServices' => function (): array {
                return [
                    new DoctrineDbalChecker(self::$_this->createHealthyDbalRegistry(), self::$_this->createLogger()),
                    new DoctrineMongoDBChecker(self::$_this->createHealthyMongodbRegistry(), self::$_this->createLogger())
                ];
            },
            'expected' => [
                'statusCode' => 0,
                'displayMessage' => "doctrine_dbal: ok\ndoctrine_mongodb: ok\n"
            ]
        ];

        yield 'failed with mongodb' => [
            'checkers' => [DoctrineDbalChecker::NAME, DoctrineMongoDBChecker::NAME],
            'checkerServices' => static function (): array {
                return [
                    new DoctrineDbalChecker(self::$_this->createHealthyDbalRegistry(), self::$_this->createLogger()),
                    new DoctrineMongoDBChecker(
                        self::$_this->createUnhealthyMongodbRegistry(),
                        self::$_this->createLogger('error', '[health-check] doctrine_mongodb failed, reason MongoDB connection failed')
                    )
                ];
            },
            'expected' => [
                'statusCode' => 1,
                'displayMessage' => "doctrine_dbal: ok\ndoctrine_mongodb: ko\n"
            ]
        ];

        yield 'with checkers not in checker services' => [
            'checkers' => ['some-checker'],
            'checkerServices' => static function (): array {
                $checkerMock = self::$_this->createMock(CheckerInterface::class);
                $checkerMock->expects(self::$_this->never())->method('isOk');
                $checkerMock->method('getName')->willReturn('other_checker');

                return [
                    $checkerMock
                ];
            },
            'expected' => [
                'statusCode' => 0,
                'displayMessage' => ''
            ]
        ];
    }
}
