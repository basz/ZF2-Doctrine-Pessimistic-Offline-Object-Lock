<?php

namespace HF\POOL\Factory;

use HF\POOL\EventSubscriber\PoolListener;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PoolListenerFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $objectLockManager     = $serviceLocator->get('hf_pool.manager');
        $authenticationService = $serviceLocator->get('hf_pool.authenticationService');

        return new PoolListener($objectLockManager, $authenticationService);
    }
}
