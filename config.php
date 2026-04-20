<?php
declare(strict_types=1);

return [

    'app_name' => 'KV Monitor Enterprise',
    'refresh_seconds' => 5,
    'timezone' => 'America/Chicago',

    'groups' => [

        'Infrastructure' => [
            [
                'name' => 'Nginx',
                'type' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 80,
                'icon' => '🌐',
                'description' => 'Primary web server',
                'url' => 'http://127.0.0.1'
            ],
            [
                'name' => 'PHP-FPM',
                'type' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 9000,
                'icon' => '🐘',
                'description' => 'PHP FastCGI process manager',
                'url' => null
            ],
            [
                'name' => 'Cockpit',
                'type' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 9090,
                'icon' => '🖥️',
                'description' => 'Server administration console',
                'url' => 'https://127.0.0.1:9090'
            ],
        ],

        'Security' => [
            [
                'name' => 'Vaultwarden',
                'type' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 8080,
                'icon' => '🔐',
                'description' => 'Password vault service',
                'url' => 'http://127.0.0.1:8080'
            ],
        ],

    ],

];
