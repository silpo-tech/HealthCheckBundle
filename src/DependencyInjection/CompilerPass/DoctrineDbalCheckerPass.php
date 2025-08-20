<?php

declare(strict_types=1);

namespace HealthCheck\DependencyInjection\CompilerPass;

use HealthCheck\Checker\DoctrineDbalChecker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineDbalCheckerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $web = $container->getParameter('web.checkers');
        $command = $container->getParameter('command.checkers');

        if (!in_array(DoctrineDbalChecker::NAME, $web) && !in_array(DoctrineDbalChecker::NAME, $command)) {
            $container->removeDefinition(DoctrineDbalChecker::class);

            return;
        }

        if (!$container->hasDefinition('doctrine')) {
            throw new \LogicException(
                "Doctrine DBAL health checker can't be enabled, 'doctrine' service was not found",
            );
        }

        $definition = $container->getDefinition(DoctrineDbalChecker::class);
        $definition->setArgument(0, new Reference('doctrine'));
        $definition->addTag('health_check.checker');
    }
}
