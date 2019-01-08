<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // View settings
        'view'                              => [
            'template_path' => __DIR__ . '/../templates',
            'twig'          => [
                'cache'       => __DIR__ . '/../storage/cache/twig',
                'debug'       => true,
                'auto_reload' => true,
                'sitename'    => 'WatWaarWanneer'
            ],
        ],

        // Datatbase settings
        'db'                                => [
            'driver'   => 'mysql',
            'host'     => 'localhost:8889',
            'database' => 'your_db_name',
            'username' => 'username',
            'password' => 'userpass',
            'charset'  => 'UTF8',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
    ],
];
