<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log Levels
    |--------------------------------------------------------------------------
    |
    | The log levels that should be captured and stored in the database.
    |
    */
    'log_levels' => [
        'error',
        'critical',
        'emergency',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Exceptions
    |--------------------------------------------------------------------------
    |
    | List of exception classes that should be ignored by the Nitik log driver.
    |
    */
    'ignore_exceptions' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used for the package tables.
    |
    */
    'table_prefix' => 'nitik_',

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The navigation group label for Filament resource.
    | Set to null to disable grouping.
    |
    */
    'navigation_group' => null,

    /*
    |--------------------------------------------------------------------------
    | Navigation Label & Icon
    |--------------------------------------------------------------------------
    |
    | Custom label and icon for the Filament resource menu.
    |
    */
    'navigation_label' => 'Error Tracker',
    'navigation_icon' => 'heroicon-o-bug-ant',
];
