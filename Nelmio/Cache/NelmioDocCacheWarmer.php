<?php

namespace Emhar\ApiInfrastructureBundle\Nelmio\Cache;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * {@inheritDoc}
 */
class NelmioDocCacheWarmer implements CacheWarmerInterface
{

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var bool
     */
    protected $warnNelmioDoc;

    /**
     * NelmioDocCacheWarmer constructor.
     * @param KernelInterface $kernel
     * @param bool $warnNelmioDoc
     */
    public function __construct(KernelInterface $kernel, bool $warnNelmioDoc)
    {
        $this->kernel = $kernel;
        $this->warnNelmioDoc = $warnNelmioDoc;
    }


    /**
     * {@inheritDoc}
     */
    public function isOptional()
    {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    public function warmUp($cacheDir)
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'api:doc:dump',
            '--format' => 'html',
        ));
        // You can use NullOutput() if you don't need the output
        $output = new NullOutput();
        $application->run($input, $output);
    }
}