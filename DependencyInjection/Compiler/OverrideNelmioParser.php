<?php

namespace Emhar\ApiInfrastructureBundle\DependencyInjection\Compiler;

use Emhar\ApiInfrastructureBundle\Nelmio\Parser\InheritanceAwareJmsMetadataParser;
use Emhar\ApiInfrastructureBundle\Nelmio\Parser\TableizedNameValidationParser;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * {@inheritDoc}
 */
class OverrideNelmioParser implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if($container->hasDefinition('nelmio_api_doc.parser.validation_parser')) {
            $definition = $container->getDefinition('nelmio_api_doc.parser.validation_parser');
            $definition->setClass(TableizedNameValidationParser::class);
            $definition->addMethodCall('setJmsParser', array(new Reference('nelmio_api_doc.parser.jms_metadata_parser')));
        }

        if($container->hasDefinition('nelmio_api_doc.parser.jms_metadata_parser')) {
            $definition = $container->getDefinition('nelmio_api_doc.parser.jms_metadata_parser');
            $definition->setClass(InheritanceAwareJmsMetadataParser::class);
            $definition->addMethodCall('setDoctrineRegistry', array(new Reference('doctrine')));
        }
    }
}
