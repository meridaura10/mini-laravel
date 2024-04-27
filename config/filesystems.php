<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => app()->storagePath('app'),
            'throw' => false,
        ],
        'public' => [
            'driver' => 'local',
            'root' => app()->storagePath('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        app()->publicPath('storage') => app()->storagePath('app/public')
    ],
];