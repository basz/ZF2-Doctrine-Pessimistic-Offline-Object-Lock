<?php

namespace HF\POOL\Factory;

use HF\POOL\Service\ObjectLockManager;
use HF\POOL\Option\ObjectLockOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ObjectLockManagerFactory implements FactoryInterface
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
        $options    = $serviceLocator->get('hf_pool.options');
        $connection = $serviceLocator->get($options->getDoctrineConnection());

        return new ObjectLockManager($connection, $options);
    }
}
