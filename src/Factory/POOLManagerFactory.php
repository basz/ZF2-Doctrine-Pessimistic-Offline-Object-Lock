<?php

namespace HF\POOL\Factory;

use HF\POOL\Options\ObjectLockOptions;
use HF\POOL\Service\POOLManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class POOLManagerFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var ObjectLockOptions $options */
        $options        = $serviceLocator->get('hf_pool.options');
        $connection     = $serviceLocator->get($options->getDoctrineConnection());
        $userIdProvider = $serviceLocator->get('hf_pool.provider.userid');

        return new POOLManager($connection, $userIdProvider, $options);
    }
}
