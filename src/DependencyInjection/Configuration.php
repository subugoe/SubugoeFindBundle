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
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('subugoe_find');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('facets')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('field')->end()
                            ->scalarNode('title')->end()
                            ->scalarNode('sort')->defaultValue('count')->end()
                            ->scalarNode('quantity')->defaultValue(100)->end()
                        ->end()
                    ->end()
                ->end()
            ->integerNode('results_per_page')
                ->defaultValue(15)
            ->end()
            ->integerNode('feed_rows')
                ->defaultValue(15)
            ->end()
            ->scalarNode('feed_category')
                ->defaultValue('dc')
            ->end()
            ->scalarNode('default_query')
                ->defaultValue('*:*')
            ->end()
            ->scalarNode('default_sort')
                ->defaultValue('score desc')
            ->end()
            ->arrayNode('default_fields')
                ->prototype('scalar')->end()
            ->end()
            ->scalarNode('feed_sort')
                ->defaultValue('')
            ->end()
            ->arrayNode('feed_fields')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('hidden')
                ->prototype('array')
                    ->children()
                        ->scalarNode('field')->end()
                        ->scalarNode('value')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('snippet')
                ->children()
                    ->scalarNode('count')->end()
                    ->scalarNode('length')->end()
                    ->scalarNode('prefix')->end()
                    ->scalarNode('postfix')->end()
                    ->scalarNode('field')->end()
                    ->scalarNode('sort')->end()
                    ->scalarNode('sort_dir')->end()
                    ->scalarNode('page_fulltext')->end()
                    ->scalarNode('page_number')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
