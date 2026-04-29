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
            'driver' => 'ftp',
            'host' => env('FTP_HOST'),
            'username' => env('FTP_USERNAME'),
            'password' => env('FTP_PASSWORD'),
            'port' => env('FTP_PORT', 21),
            'root' => env('FTP_ROOT', ''),
            'passive' => true,
            'ssl' => false,
            'timeout' => 30,
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

        'cloudinary' => [
            'driver' => 'cloudinary',
            'key' => env('CLOUDINARY_KEY'),
            'secret' => env('CLOUDINARY_SECRET'),
            'cloud' => env('CLOUDINARY_CLOUD_NAME'),
            'url' => env('CLOUDINARY_URL'),
            'secure' => (bool)env('CLOUDINARY_SECURE', true),
            'prefix' => env('CLOUDINARY_PREFIX'),
        ],

        'idrive' => [
            'driver' => 's3',
            'key' => env('IDRIVE_API_KEY'),
            'secret' => env('IDRIVE_API_SECRET'),
            'region' => env('IDRIVE_REGION'),
            'bucket' => env('IDRIVE_BUCKET'),
            'version' => env('IDRIVE_VERSION'),
            'endpoint' => env('IDRIVE_ENDPOINT'),
            'use_path_style_endpoint' => true,
        ],

        'azure' => [
            'driver' => 'azure',
            'name' => env('AZURE_STORAGE_NAME'),
            'key' => env('AZURE_STORAGE_KEY'),
            'container' => env('AZURE_STORAGE_CONTAINER_NAME'),
            'url' => env('AZURE_STORAGE_URL'),
            'prefix' => null,
            'connection_string' => env('AZURE_STORAGE_CONNECTION_STRING')
        ],

    ],


    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
