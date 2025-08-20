<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Integration\Command;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use HealthCheck\Checker\DoctrineDbalChecker;
use HealthCheck\Checker\DoctrineMongoDBChecker;
use HealthCheck\Command\HealthCommand;
use HealthCheck\HealthCheckBundle;
use HealthCheck\Tests\Stub\Kernel;
use HealthCheck\Tests\Traits\MockTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @see HealthCommand
 */
class HealthCommandTest extends KernelTestCase
{
    use MockTrait;

    public static function setUpBeforeClass(): void
    {
        self::$kernel = new Kernel('test', true);
        self::$kernel->boot();
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /**
         * @var Kernel $kernel
         */
        $kernel = parent::createKernel($options);

        $kernel->addTestBundle(HealthCheckBundle::class);
        $kernel->addTestBundle(DoctrineBundle::class);
        $kernel->addTestBundle(DoctrineMongoDBBundle::class);

        $kernel->addTestConfig(__DIR__ . '/../../../Resources/config/packages/health_check.yaml');
        $kernel->addTestConfig(__DIR__ . '/../../../Resources/config/packages/doctrine.yaml');
        $kernel->addTestConfig(__DIR__ . '/../../../Resources/config/packages/doctrine_mongodb.yaml');

        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testOk(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('health:check');

        $application->getKernel()->getContainer()->set(
            DoctrineDbalChecker::class,
            new DoctrineDbalChecker($this->createHealthyDbalRegistry(), $this->createLogger())
        );

        $application->getKernel()->getContainer()->set(
            DoctrineMongoDBChecker::class,
            new DoctrineMongoDBChecker($this->createHealthyMongodbRegistry(), $this->createLogger())
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertEquals("doctrine_dbal: ok\ndoctrine_mongodb: ok\n", $commandTester->getDisplay());
    }

    public function testFail(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('health:check');

        $application->getKernel()->getContainer()->set(
            DoctrineDbalChecker::class,
            new DoctrineDbalChecker($this->createHealthyDbalRegistry(), $this->createLogger())
        );

        $application->getKernel()->getContainer()->set(
            DoctrineMongoDBChecker::class,
            new DoctrineMongoDBChecker(
                $this->createUnhealthyMongodbRegistry(),
                $this->createLogger('error', '[health-check] doctrine_mongodb failed, reason MongoDB connection failed')
            )
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertEquals("doctrine_dbal: ok\ndoctrine_mongodb: ko\n", $commandTester->getDisplay());
    }
}
