<?php

return [

    'tenant_database' => 'tenants',

    'tenant_model' => \App\Models\Tenant::class,

    'central_domains' => [
        '127.0.0.1',
        'localhost',
    ],

    'tenant_id_column' => 'id',

    'cache_store' => 'database',

    'central_database' => [
        'default' => 'central',
    ],

    'central_migrations' => [
        'create_users_table',
        'create_tenants_table',
    ],

    'tenant' => [
        'database' => [
            'suffix' => 'tenant_',
            'driver' => 'mysql',
            'host' => env('TENANCY_DATABASE_HOST', '127.0.0.1'),
            'port' => env('TENANCY_DATABASE_PORT', '3306'),
            'username' => env('TENANCY_DATABASE_USERNAME', 'root'),
            'password' => env('TENANCY_DATABASE_PASSWORD', ''),
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
                'host' => env('TENANCY_DATABASE_HOST', 'mysql'),
                'port' => env('TENANCY_DATABASE_PORT', '3306'),
                'username' => env('TENANCY_DATABASE_USERNAME', 'sail'),
                'password' => env('TENANCY_DATABASE_PASSWORD', 'password'),
                'database' => null,
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

