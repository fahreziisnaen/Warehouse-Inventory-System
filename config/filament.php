<?php

return [
    'path' => env('FILAMENT_PATH', 'admin'),
    'domain' => env('FILAMENT_DOMAIN'),
    'home_url' => '/',
    'brand' => env('APP_NAME'),
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
    ],
    'middleware' => [
        'base' => [
            'auth',
            'verified',
        ],
        'auth' => [
            'auth',
        ],
    ],
    'pages' => [
        'namespace' => 'App\\Filament\\Pages',
    ],
    'resources' => [
        'namespace' => 'App\\Filament\\Resources',
    ],
    'widgets' => [
        'namespace' => 'App\\Filament\\Widgets',
    ],
]; 