<?php

declare(strict_types=1);

namespace HealthCheck\DependencyInjection\CompilerPass;

use HealthCheck\Checker\DoctrineMongoDBChecker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineMongoDBCheckerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $web = $container->getParameter('web.checkers');
        $command = $container->getParameter('command.checkers');

        if (!in_array(DoctrineMongoDBChecker::NAME, $web) && !in_array(DoctrineMongoDBChecker::NAME, $command)) {
            $container->removeDefinition(DoctrineMongoDBChecker::class);

            return;
        }

        if (!$container->hasDefinition('doctrine_mongodb')) {
            throw new \LogicException("Doctrine MongoDB health checker can't be enabled, 'doctrine_mongodb' service was not found");
        }

        $definition = $container->getDefinition(DoctrineMongoDBChecker::class);
        $definition->setArgument(0, new Reference('doctrine_mongodb'));
        $definition->addTag('health_check.checker');
    }
}
