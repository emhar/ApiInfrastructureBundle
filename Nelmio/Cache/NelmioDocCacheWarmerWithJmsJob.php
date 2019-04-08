<?php

namespace Emhar\ApiInfrastructureBundle\Nelmio\Cache;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Types\Type;
use JMS\JobQueueBundle\Entity\Job;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * {@inheritDoc}
 */
class NelmioDocCacheWarmerWithJmsJob implements CacheWarmerInterface
{

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var bool
     */
    protected $warnNelmioDoc;

    /**
     * NelmioDocCacheWarmer constructor.
     * @param Registry $doctrine
     * @param bool $warnNelmioDoc
     */
    public function __construct(Registry $doctrine, bool $warnNelmioDoc)
    {
        $this->doctrine = $doctrine;
        $this->warnNelmioDoc = $warnNelmioDoc;
    }


    /**
     * {@inheritDoc}
     */
    public function isOptional(): bool
    {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    public function warmUp($cacheDir)
    {
        if ($this->warnNelmioDoc) {
            $commandName = 'api:doc:dump';
            $args = array('--format=html');

            $em = $this->doctrine->getManager();
            $pendingJob = $em
                ->createQuery(
                    'SELECT j FROM JMSJobQueueBundle:Job j'
                    . ' WHERE j.command = :command AND j.args = :args AND j.state = :state'
                )
                ->setParameter('command', $commandName)
                ->setParameter('args', $args, Type::JSON_ARRAY)
                ->setParameter('state', Job::STATE_PENDING)
                ->setMaxResults(1)
                ->getOneOrNullResult();
            if (!$pendingJob) {
                try {
                    $date = new \DateTime();
                    $date->add(new \DateInterval('PT5M'));
                } catch (\Exception $e) {
                    trigger_error($e->getMessage(), E_USER_ERROR);
                    $date = null;
                }
                $job = new Job($commandName, $args);
                $job->setExecuteAfter($date);
                $this->doctrine->getManager()->persist($job);
                $this->doctrine->getManager()->flush();
            }
        }
    }
}