<?php

namespace Emhar\ApiInfrastructureBundle\DependencyInjection\Compiler;

use Emhar\ApiInfrastructureBundle\Serializer\EnumHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * {@inheritDoc}
 */
class OverrideEnumHandlerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('fervo_enum.jms_serializer.enum_handler');
        $definition->setClass(EnumHandler::class);
        $definition->addMethodCall('setTranslator', array(new Reference('translator')));
    }
}
