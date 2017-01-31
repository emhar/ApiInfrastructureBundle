<?php

namespace Emhar\ApiInfrastructureBundle\DependencyInjection\Compiler;

use Emhar\ApiInfrastructureBundle\FosRest\RequestBodyParamConverter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * {@inheritDoc}
 */
class OverrideFosRestRequestBodyParamConverter implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('fos_rest.converter.request_body');
        $definition->setClass(RequestBodyParamConverter::class);
    }
}
