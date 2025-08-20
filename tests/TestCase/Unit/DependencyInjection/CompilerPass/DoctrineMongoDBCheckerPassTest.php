<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Unit\DependencyInjection\CompilerPass;

use HealthCheck\Checker\DoctrineMongoDBChecker;
use HealthCheck\DependencyInjection\CompilerPass\DoctrineMongoDBCheckerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DoctrineMongoDBCheckerPassTest extends TestCase
{
    private ContainerBuilder $container;
    private DoctrineMongoDBCheckerPass $compilerPass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compilerPass = new DoctrineMongoDBCheckerPass();

        $this->container->setDefinition(DoctrineMongoDBChecker::class, new Definition());
    }

    public function testRemovesCheckerIfNotInConfig(): void
    {
        $this->container->setParameter('web.checkers', []);
        $this->container->setParameter('command.checkers', []);

        $this->compilerPass->process($this->container);

        $this->assertFalse($this->container->hasDefinition(DoctrineMongoDBChecker::class));
    }

    public function testThrowsExceptionIfDoctrineServiceIsMissing(): void
    {
        $this->container->setParameter('web.checkers', [DoctrineMongoDBChecker::NAME]);
        $this->container->setParameter('command.checkers', []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Doctrine MongoDB health checker can't be enabled, 'doctrine_mongodb' service was not found");

        $this->compilerPass->process($this->container);
    }

    public function testConfiguresCheckerIfDoctrineExists(): void
    {
        $this->container->setParameter('web.checkers', [DoctrineMongoDBChecker::NAME]);
        $this->container->setParameter('command.checkers', []);

        $this->container->setDefinition('doctrine_mongodb', new Definition());

        $this->compilerPass->process($this->container);

        $this->assertTrue($this->container->hasDefinition(DoctrineMongoDBChecker::class));

        $definition = $this->container->getDefinition(DoctrineMongoDBChecker::class);

        $this->assertEquals('doctrine_mongodb', (string) $definition->getArgument(0));

        $this->assertTrue($definition->hasTag('health_check.checker'));
    }
}