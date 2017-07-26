<?php

namespace Emhar\ApiInfrastructureBundle;

use Emhar\ApiInfrastructureBundle\DependencyInjection\Compiler\OverrideEnumHandlerPass;
use Emhar\ApiInfrastructureBundle\DependencyInjection\Compiler\OverrideFosRestRequestBodyParamConverter;
use Emhar\ApiInfrastructureBundle\DependencyInjection\Compiler\OverrideNelmioParser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * {@inheritDoc}
 */
class EmharApiInfrastructureBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new OverrideNelmioParser());
        $container->addCompilerPass(new OverrideFosRestRequestBodyParamConverter());
    }
}