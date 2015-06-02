<?php

namespace HF\POOL\Factory;

use HF\POOL\Service\Delegator\DoctrineEntityDelegator;
use HF\POOL\Service\DoctrinePOOLManager;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DoctrinePOOLManagerFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $delegatedService   = $serviceLocator->get('hf_pool.manager');

        return new DoctrinePOOLManager($delegatedService);
    }
}