<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'fatafat_cdn' => [
            'driver' => 'ftp',
            'host' => env('IMG_FTP_HOST'),
            'username' => env('IMG_FTP_USERNAME'),
            'password' => env('IMG_FTP_PASSWORD'),
            'port' => 21,

            'passive' => true,   // recommended
            'ssl' => false,      // usually false unless using FTPS
            'timeout' => 30,

            'root' => env('IMG_FTP_ROOT','/public_html'),
            'url' => env('IMG_BASE_URL','https://img.fatafatsewa.com')
        ],

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('MEDIA_URL', 'https://fatafatsewa.com/storage'),
            'visibility' => 'public',
            'throw' => false,
        ],

        'media' => [
            'driver' => 'local',
            'root' => public_path('storage/media'),
            'url' => env('MEDIA_URL', 'https://fatafatsewa.com/storage') . '/media',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
