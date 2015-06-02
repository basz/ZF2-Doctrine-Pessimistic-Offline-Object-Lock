<?php

namespace HF\POOL\Factory;

use HF\POOL\EventSubscriber\DoctrineLockListener;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DoctrineLockListenerFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $poolManager           = $serviceLocator->get('hf_pool.doctrine.manager');

        $userIdProvider = $serviceLocator->get('hf_pool.provider.userid');

        return new DoctrineLockListener($poolManager, $userIdProvider);
    }
}
