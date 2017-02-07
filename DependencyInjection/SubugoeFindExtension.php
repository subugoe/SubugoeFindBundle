<?php

namespace Subugoe\FindBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SubugoeFindExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('facets', $config['facets']);
        $container->setParameter('results_per_page', $config['results_per_page']);
        $container->setParameter('hidden', $config['hidden']);
        $container->setParameter('default_query', $config['default_query']);
        $container->setParameter('default_sort', $config['default_sort']);
        $container->setParameter('product_fields', $config['product_fields']);
        $container->setParameter('mets_fields', $config['mets_fields']);
        $container->setParameter('anchor_fields', $config['anchor_fields']);
        $container->setParameter('toc_fields', $config['toc_fields']);
        $container->setParameter('default_fields', $config['default_fields']);
        $container->setParameter('structure_fields', $config['structure_fields']);
        $container->setParameter('bibliographic_fields', $config['bibliographic_fields']);
        $container->setParameter('volumes_fields', $config['volumes_fields']);
        $container->setParameter('volumes_children_fields', $config['volumes_children_fields']);
        $container->setParameter('details_fields', $config['details_fields']);
        $container->setParameter('feed_sort', $config['feed_sort']);
        $container->setParameter('feed_rows', $config['feed_rows']);
        $container->setParameter('feed_fields', $config['feed_fields']);
        $container->setParameter('feed_category', $config['feed_category']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    public function getAlias()
    {
        return 'subugoe_find';
    }
}
