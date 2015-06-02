<?php

return [
    'lazy_services'   => [
        'class_map' => [
            // use the alias
            'hf_pool.manager'               => HF\POOL\Service\POOLManager::class,
            'hf_pool.doctrine.locklistener' => HF\POOL\EventSubscriber\DoctrineLockListener::class,
        ],
    ],
    'service_manager' => [
        'aliases'    => [
            'hf_pool.provider.userid'       => HF\POOL\Provider\UserId\AuthenticationService::class,
            'hf_pool.options'               => HF\POOL\Options\ObjectLockOptions::class,
            'hf_pool.manager'               => HF\POOL\Service\POOLManager::class,
            'hf_pool.doctrine.manager'      => HF\POOL\Service\DoctrinePOOLManager::class,
            'hf_pool.doctrine.locklistener' => HF\POOL\EventSubscriber\DoctrineLockListener::class,
        ],
        'invokables' => [
            HF\POOL\Provider\UserId\Request::class => HF\POOL\Provider\UserId\Request::class,
        ],
        'factories'  => [
            HF\POOL\EventSubscriber\DoctrineLockListener::class         => HF\POOL\Factory\DoctrineLockListenerFactory::class,
            HF\POOL\Service\POOLManager::class                          => HF\POOL\Factory\POOLManagerFactory::class,
            HF\POOL\Service\DoctrinePOOLManager::class                  => HF\POOL\Factory\DoctrinePOOLManagerFactory::class,
            HF\POOL\Options\ObjectLockOptions::class                    => HF\POOL\Factory\ObjectLockOptionsFactory::class,
            HF\POOL\Provider\UserId\AuthenticationService::class        => HF\POOL\Provider\UserId\AuthenticationServiceFactory::class,
            HF\POOL\Provider\UserId\ZfcUserAuthenticationService::class => HF\POOL\Provider\UserId\ZfcUserAuthenticationServiceFactory::class,
            // delegated lazy loading
            'hf_pool.delegator.lazy'                                    => Zend\ServiceManager\Proxy\LazyServiceFactoryFactory::class,
        ],
        'delegators' => [
            HF\POOL\Service\POOLManager::class                  => [
                'hf_pool.delegator.lazy',
            ],
            HF\POOL\EventSubscriber\DoctrineLockListener::class => [
                'hf_pool.delegator.lazy',
            ],
        ],
    ],
    'doctrine'        => [
        'driver'       => [
            'hf_pool_xml_driver' => [
                'class' => Doctrine\ORM\Mapping\Driver\XmlDriver::class,
                'paths' => [
                    __DIR__ . '/orm-xml',
                ],
            ],
            'orm_default'        => [
                'class'   => Doctrine\ORM\Mapping\Driver\DriverChain::class,
                'drivers' => [
                    'HF\POOL' => 'hf_pool_xml_driver',
                ],
            ],
        ],
        'eventmanager' => [
            'orm_default' => [
                'subscribers' => [
                    // delegated to \Zend\ServiceManager\Proxy\LazyServiceFactoryFactory
                    'hf_pool.doctrine.locklistener',
                ],
            ],
        ],
    ],
];