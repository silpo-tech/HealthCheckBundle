<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Application\Command;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use HealthCheck\Command\HealthCommand;
use HealthCheck\HealthCheckBundle;
use HealthCheck\Tests\Stub\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @see HealthCommand
 */
class HealthCommandTest extends KernelTestCase
{
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

        $kernel->addTestConfig(__DIR__.'/../../../Resources/config/packages/health_check.yaml');
        $kernel->addTestConfig(__DIR__.'/../../../Resources/config/packages/doctrine.yaml');
        $kernel->addTestConfig(__DIR__.'/../../../Resources/config/packages/doctrine_mongodb.yaml');

        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testOk(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);

        $dropCommand = $application->find('doctrine:database:drop');
        $createCommand = $application->find('doctrine:database:create');

        $commandTester = new CommandTester($dropCommand);
        $commandTester->execute(['--force' => true]);

        $commandTester = new CommandTester($createCommand);
        $commandTester->execute([]);

        $command = $application->find('health:check');

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertEquals("doctrine_dbal: ok\ndoctrine_mongodb: ok\n", $commandTester->getDisplay());
    }
}
