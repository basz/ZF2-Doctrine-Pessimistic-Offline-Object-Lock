<?php

return [
    'doctrine' => [
        'driver'     => [
            'hf_pool_xml_driver' => [
                'paths' => [
                    __DIR__ . '/orm-xml',
                ],
            ],
            'orm_default'        => [
                'drivers' => [
                    'HFTest\POOL' => 'hf_pool_xml_driver',
                ]
            ],
        ],
        'connection' => [
            'orm_default' => [
                'configuration' => 'orm_default',
                'eventmanager'  => 'orm_default',
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params'      => [
                    'host'          => 'localhost',
                    'port'          => '3306',
                    'user'          => 'travis',
                    'password'      => '',
                    'dbname'        => 'travis-test',
//                    'unix_socket'   => '/tmp/mysql.sock',
                    'charset'       => 'utf8',
                    'driverOptions' => [
                        1002 => 'SET NAMES utf8'
                    ],
                ],
            ],
        ],
    ],
    'lazy_services'   => [
        // directory where proxy classes will be written - default to system_get_tmp_dir()
        'proxies_target_dir'    => __DIR__ . '/../data/lazy_services_proxy',
        // namespace of the generated proxies, default to "ProxyManagerGeneratedProxy"
        'proxies_namespace'     => 'LazyServiceProxy',
        // whether the generated proxy classes should be written to disk
        'write_proxy_files'     => true,
    ],

];