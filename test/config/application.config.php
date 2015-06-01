<?php


return [
    'modules'                 => [
        'DoctrineModule',
        'DoctrineORMModule',
    ],
    'module_listener_options' => [
        'config_glob_paths' => [
            __DIR__ . '/../../config/module.config.php',
            __DIR__ . '/testing.config.php',
        ],
        'module_paths'      => [
        ],
    ],
];