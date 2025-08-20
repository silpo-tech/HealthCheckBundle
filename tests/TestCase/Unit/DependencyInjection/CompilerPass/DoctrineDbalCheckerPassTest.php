<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Unit\DependencyInjection\CompilerPass;

use HealthCheck\Checker\DoctrineDbalChecker;
use HealthCheck\DependencyInjection\CompilerPass\DoctrineDbalCheckerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DoctrineDbalCheckerPassTest extends TestCase
{
    private ContainerBuilder $container;
    private DoctrineDbalCheckerPass $compilerPass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compilerPass = new DoctrineDbalCheckerPass();

        $this->container->setDefinition(DoctrineDbalChecker::class, new Definition());
    }

    public function testRemovesCheckerIfNotInConfig(): void
    {
        $this->container->setParameter('web.checkers', []);
        $this->container->setParameter('command.checkers', []);

        $this->compilerPass->process($this->container);

        $this->assertFalse($this->container->hasDefinition(DoctrineDbalChecker::class));
    }

    public function testThrowsExceptionIfDoctrineServiceIsMissing(): void
    {
        $this->container->setParameter('web.checkers', [DoctrineDbalChecker::NAME]);
        $this->container->setParameter('command.checkers', []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Doctrine DBAL health checker can't be enabled, 'doctrine' service was not found");

        $this->compilerPass->process($this->container);
    }

    public function testConfiguresCheckerIfDoctrineExists(): void
    {
        $this->container->setParameter('web.checkers', [DoctrineDbalChecker::NAME]);
        $this->container->setParameter('command.checkers', []);

        $this->container->setDefinition('doctrine', new Definition());

        $this->compilerPass->process($this->container);

        $this->assertTrue($this->container->hasDefinition(DoctrineDbalChecker::class));

        $definition = $this->container->getDefinition(DoctrineDbalChecker::class);

        $this->assertEquals('doctrine', (string) $definition->getArgument(0));

        $this->assertTrue($definition->hasTag('health_check.checker'));
    }
}
