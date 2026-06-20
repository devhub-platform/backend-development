<?php

use App\Models\User;
use App\Models\AdminUser;

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | All database tables created by this plugin will use this prefix
    | to avoid collisions with the host application's tables.
    |
    */
    'table_prefix' => 'tc_',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of the user model.
    |
    */
    'user_model' => AdminUser::class,

    /*
    |--------------------------------------------------------------------------
    | Polling Intervals (in seconds)
    |--------------------------------------------------------------------------
    */
    'polling' => [
        'messages' => 3,
        'sidebar' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'disk' => 'public',
        'directory' => 'team-chat-attachments',
        'max_size' => 10240, // KB
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Enable multi-tenancy to scope channels and conversations per team.
    | When enabled, set the tenant model and the method to resolve the
    | current tenant ID (e.g. from Filament's tenant or auth).
    |
    */
    'tenancy' => [
        'enabled' => true,
        'model' => null, // e.g. \App\Models\Team::class
        'resolver' => null, // callable or class that returns current tenant ID
    ],
];
