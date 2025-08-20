<?php

declare(strict_types=1);

namespace HealthCheck\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('health_check');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            ->arrayNode('apps')
            ->example(['web', 'command'])
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->arrayNode('checkers')
            ->scalarPrototype()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
