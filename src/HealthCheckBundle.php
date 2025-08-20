<?php

declare(strict_types=1);

namespace HealthCheck;

use HealthCheck\DependencyInjection\CompilerPass\DoctrineDbalCheckerPass;
use HealthCheck\DependencyInjection\CompilerPass\DoctrineMongoDBCheckerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HealthCheckBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container
            ->addCompilerPass(new DoctrineDbalCheckerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100)
            ->addCompilerPass(new DoctrineMongoDBCheckerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100)
        ;
    }
}
