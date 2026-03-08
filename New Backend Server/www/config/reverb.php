<?php

return [
    'server' => [
        'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
        'port' => (int) env('REVERB_SERVER_PORT', 8080),
        'hostname' => env('REVERB_HOST', 'localhost'),
        'scheme' => env('REVERB_SCHEME', 'http'),
        'options' => [
            'tls' => [
                // Provide TLS options if securing wss directly in development
            ],
        ],
    ],

    'apps' => [
        [
            'app_id' => '123456',
            'key' => '111111',
            'secret' => '222222',
            'allowed_origins' => ['*'],
            'ping_interval' => 10,
            'max_message_size' => 10000,
        ],
    ],
];

