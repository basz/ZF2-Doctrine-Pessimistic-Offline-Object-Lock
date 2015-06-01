<?php

namespace HF\POOL\Factory;

use HF\POOL\Option\ObjectLockOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ObjectLockOptionsFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');

        $config = isset($config['hf_pool']) ? $config['hf_pool'] : [];

        return new ObjectLockOptions($config);
    }
}
