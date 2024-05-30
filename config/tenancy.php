<?php

return [

    'tenant_database' => 'tenants',

    'tenant_model' => \App\Models\Tenant::class,

    'central_domains' => [
        '127.0.0.1',
        'localhost',
    ],

    'tenant_id_column' => 'id',

    'cache_store' => 'default',

    'central_database' => [
        'default' => 'central',
    ],

    'central_migrations' => [
        'create_users_table',
        'create_tenants_table',
    ],

    'tenant' => [
        'database' => [
            'suffix' => 'tenant_', // テナントDBの接頭辞
            'driver' => 'mysql',
            'host' => env('TENANCY_DB_HOST', '127.0.0.1'),
            'port' => env('TENANCY_DB_PORT', '3306'),
            'username' => env('TENANCY_DB_USERNAME', 'root'),
            'password' => env('TENANCY_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],

    'database' => [
        'managers' => [
            Stancl\Tenancy\Database\DatabaseManager::class,
        ],
        'templates' => [],
        'default_connection' => env('TENANCY_DEFAULT_CONNECTION', 'tenant'),
        'connections' => [
            'tenant' => [
                'driver' => 'mysql',
                'host' => env('TENANT_DB_HOST', '127.0.0.1'),
                'port' => env('TENANT_DB_PORT', '3306'),
                'username' => env('TENANT_DB_USERNAME', 'root'),
                'password' => env('TENANT_DB_PASSWORD', ''),
                'database' => null, // テナントごとに動的に設定
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
        ],
    ],

    'identification_middleware' => [
        \App\Http\Middleware\InitializeTenancyMiddleware::class,
    ],

    'tenant_resolver' => \App\Resolvers\CustomCachedTenantResolver::class,
];
