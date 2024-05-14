<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Database
    |--------------------------------------------------------------------------
    |
    | The name of the database used by the tenants. This database will be
    | created for each tenant and will contain their data.
    |
    */

    'tenant_database' => 'tenants',

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The model that represents a tenant in your application.
    |
    */

    'tenant_model' => \App\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Tenant ID Column
    |--------------------------------------------------------------------------
    |
    | The column used to determine the tenant's unique identifier.
    |
    */

    'tenant_id_column' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | The cache store used for tenant configurations.
    |
    */

    'cache_store' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Central Database
    |--------------------------------------------------------------------------
    |
    | The database connection used for central database operations.
    |
    */

    'central_database' => [
        'default' => 'central',
    ],

    /*
    |--------------------------------------------------------------------------
    | Central Migrations
    |--------------------------------------------------------------------------
    |
    | The migrations to be run for the central database.
    |
    */

    'central_migrations' => [
        'create_users_table',
        'create_tenants_table',
    ],

    // Additional configurations...
];
