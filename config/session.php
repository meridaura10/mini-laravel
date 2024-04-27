<?php

return [
    'driver' => env('SESSION_DRIVER', 'file'),

    'cookie' => env(
        'SESSION_COOKIE',
        \Framework\Kernel\Support\Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    'files' => app()->storagePath('framework/sessions'),

    'path' => '/',

    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE'),

    'http_only' => true,

    'same_site' => 'lax',

    'partitioned' => false,

    'expire_on_close' => false,

    'lifetime' => env('SESSION_LIFETIME', 3600),

    'encrypt' => false,
];