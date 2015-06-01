<?php

return [
    'lazy_services'   => [
        'class_map' => [
            'hf_pool.manager' => 'HF\POOL\Service\ObjectLockManager',
        ],
    ],
    'service_manager' => [
        'invokables' => [
            'hf_pool.authenticationService' => 'Zend\Authentication\AuthenticationService'
        ],
        'factories'  => [
            'hf_pool.lazy.manager' => 'Zend\ServiceManager\Proxy\LazyServiceFactoryFactory',
            'hf_pool.listener'     => 'HF\POOL\Factory\PoolListenerFactory',
            'hf_pool.manager'      => 'HF\POOL\Factory\ObjectLockManagerFactory',
            'hf_pool.options'      => 'HF\POOL\Factory\ObjectLockOptionsFactory',
        ],
        'delegators' => [
            'hf_pool.manager' => [
                'hf_pool.lazy.manager'
            ],
        ],
    ],
    'doctrine'        => [
        'driver'       => [
            'hf_pool_xml_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
                'paths' => [
                    __DIR__ . '/orm-xml',
                ],
            ],
            'orm_default'        => [
                'class'   => 'Doctrine\ORM\Mapping\Driver\DriverChain',
                'drivers' => [
                    'HF\POOL' => 'hf_pool_xml_driver',
                ],
            ],
        ],
        'eventmanager' => [
            'orm_default' => [
                'subscribers' => [
                    'hf_pool.listener'
                ],
            ],
        ],
    ],
];