<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
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
