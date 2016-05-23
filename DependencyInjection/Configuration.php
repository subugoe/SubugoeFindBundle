<?php

namespace Subugoe\FindBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('subugoe_find');
        $rootNode
            ->children()
                ->arrayNode('facets')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('field')->end()
                            ->scalarNode('title')->end()
                        ->end()
                    ->end()
                ->end()
            ->integerNode('results_per_page')
                ->defaultValue(15)
            ->end()
            ->scalarNode('default_query')
                ->defaultValue('*:*')
            ->end()
            ->arrayNode('hidden')
                ->prototype('array')
                    ->children()
                        ->scalarNode('field')->end()
                        ->scalarNode('value')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
