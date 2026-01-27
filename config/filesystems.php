<?php

return [

    'default' => env('FILESYSTEM_DISK', 's3'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-west-2'),
            'bucket' => env('AWS_BUCKET'),
            'url' => null,
            'endpoint' => null,
            'use_path_style_endpoint' => false,
            'throw' => true,
        ],

        'ftp' => [
            'driver'   => 'ftp',
            'host'     => env('FTP_HOST'),
            'username' => env('FTP_USERNAME'),
            'password' => env('FTP_PASSWORD'),
            'port'     => env('FTP_PORT', 21),
            'root'     => env('FTP_ROOT', ''),
            'passive'  => true,
            'ssl'      => false,
            'timeout'  => 30,
        ],

        'sftp' => [
            'driver' => 'sftp',
            'host' => env('SFTP_HOST'),
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'),
            'port' => env('SFTP_PORT', 22),
            'root' => env('SFTP_ROOT', ''),
            'timeout' => 30,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
