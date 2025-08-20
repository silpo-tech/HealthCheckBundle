<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use HealthCheck\Checker\CheckerInterface;
use HealthCheck\Checker\DoctrineDbalChecker;
use HealthCheck\Checker\DoctrineMongoDBChecker;
use HealthCheck\HealthCheckBundle;
use HealthCheck\Tests\Stub\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelTest extends KernelTestCase
{
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
        $kernel->handleOptions($options);

        return $kernel;
    }

    #[DataProvider('okDataProvider')]
    public function testOk(array $configs, array $dependencies, array $expected): void
    {
        $kernel = static::bootKernel(['config' => static function (Kernel $kernel) use ($configs, $dependencies) {
            foreach (array_merge([HealthCheckBundle::class], $dependencies) as $bundle) {
                $kernel->addTestBundle($bundle);
            }
            foreach ($configs as $config) {
                $kernel->addTestConfig($config);
            }
        }]);

        /** @var Container $container */
        $container = $kernel->getContainer();

        $this->assertEquals($expected['web'], $container->getParameter('web.checkers'));
        $this->assertEquals($expected['command'], $container->getParameter('command.checkers'));

        foreach ($expected['checkers'] as $checker) {
            $this->assertInstanceOf(CheckerInterface::class, $container->get($checker));
        }
    }

    public static function okDataProvider(): iterable
    {
        yield 'dbal command' => [
            'configs' => [
                __DIR__.'/../../Fixtures/config/packages/health_check_command_dbal.yaml',
                __DIR__.'/../../Resources/config/packages/doctrine.yaml',
            ],
            'dependencies' => [DoctrineBundle::class],
            'expected' => [
                'command' => ['doctrine_dbal'],
                'web' => [],
                'checkers' => [
                    DoctrineDbalChecker::class,
                ],
            ],
        ];

        yield 'mongodb command' => [
            'configs' => [
                __DIR__.'/../../Fixtures/config/packages/health_check_command_mongodb.yaml',
                __DIR__.'/../../Resources/config/packages/doctrine_mongodb.yaml',
            ],
            'dependencies' => [DoctrineMongoDBBundle::class],
            'expected' => [
                'command' => ['doctrine_mongodb'],
                'web' => [],
                'checkers' => [
                    DoctrineMongoDBChecker::class,
                ],
            ],
        ];

        yield 'mongodb + dbal command' => [
            'configs' => [
                __DIR__.'/../../Fixtures/config/packages/health_check_command.yaml',
                __DIR__.'/../../Resources/config/packages/doctrine_mongodb.yaml',
                __DIR__.'/../../Resources/config/packages/doctrine.yaml',
            ],
            'dependencies' => [DoctrineBundle::class, DoctrineMongoDBBundle::class],
            'expected' => [
                'command' => ['doctrine_dbal', 'doctrine_mongodb'],
                'web' => [],
                'checkers' => [
                    DoctrineMongoDBChecker::class,
                    DoctrineDbalChecker::class,
                ],
            ],
        ];
    }

    #[DataProvider('failDataProvider')]
    public function testFail(array $configs, array $dependencies, array $expected): void
    {
        try {
            static::bootKernel(['config' => static function (Kernel $kernel) use ($configs, $dependencies) {
                foreach (array_merge([HealthCheckBundle::class], $dependencies) as $bundle) {
                    $kernel->addTestBundle($bundle);
                }
                foreach ($configs as $config) {
                    $kernel->addTestConfig($config);
                }
            }]);
        } catch (\Throwable $e) {
            $this->assertEquals($expected['exception'], $e);

            return;
        }

        self::fail('Test fail');
    }

    public static function failDataProvider(): iterable
    {
        yield 'doctrine extension not found' => [
            'configs' => [
                __DIR__.'/../../Fixtures/config/packages/health_check_command_dbal.yaml',
            ],
            'dependencies' => [],
            'expected' => ['exception' => new \LogicException("Doctrine DBAL health checker can't be enabled, 'doctrine' service was not found")],
        ];

        yield 'doctrine_mongodb extension not found' => [
            'configs' => [
                __DIR__.'/../../Fixtures/config/packages/health_check_command_mongodb.yaml',
            ],
            'dependencies' => [],
            'expected' => ['exception' => new \LogicException("Doctrine MongoDB health checker can't be enabled, 'doctrine_mongodb' service was not found")],
        ];
    }
}
