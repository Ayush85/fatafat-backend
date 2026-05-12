<?php

return [
    'default' => env('APP_ENV') === 'local' ? 'cdn' : 'fatafat_cdn',

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

            'root' => env('IMG_FTP_ROOT', '/public_html'),
            'url' => env('IMG_BASE_URL', 'https://img.fatafatsewa.com')
        ],

        'cdn' => [
            'driver' => 'local',
            'root' => env('CDN_ROOT'),
            'url' => env('CDN_URL'),
            'visibility' => 'public',
        ],

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
