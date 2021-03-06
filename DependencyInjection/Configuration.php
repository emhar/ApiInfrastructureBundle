<?php
namespace Emhar\ApiInfrastructureBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


/**
 * {@inheritDoc}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('emhar_api_infrastructure')
            ->children()
            ->scalarNode('warm_nelmio_doc')->defaultFalse()->end()
            ->scalarNode('warm_nelmio_doc_with_jms_job')->defaultFalse()->end()
            ->end()
            ->end();
        return $treeBuilder;
    }
}
