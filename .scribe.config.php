<?php

return [
    'theme' => 'default',
    'title' => 'Fatafat API Documentation',
    'description' => 'RESTful API for Fatafat E-commerce Platform',
    'base_url' => env('APP_URL', 'http://localhost:8000'),
    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/v1/*'],
                'domains' => ['*'],
            ],
            'include' => [],
            'exclude' => [],
        ],
    ],
    'type' => 'laravel',
    'static' => [
        'output_path' => 'public/docs',
    ],
    'laravel' => [
        'add_routes' => true,
        'docs_url' => '/docs',
    ],
    'try_it_out' => [
        'enabled' => true,
        'base_url' => env('APP_URL', 'http://localhost:8000'),
    ],
    'auth' => [
        'enabled' => true,
        'default' => false,
        'in' => 'bearer',
        'name' => 'Authorization',
        'use_value' => env('SCRIBE_AUTH_KEY'),
        'placeholder' => '{YOUR_AUTH_KEY}',
        'extra_info' => 'You can retrieve your token by making a login request.',
    ],
    'intro_text' => <<<INTRO
This documentation describes the Fatafat E-commerce API.

## Authentication

Most endpoints require authentication using Laravel Sanctum. 
To authenticate, first register or login to get an access token, 
then include it in the Authorization header:

```
Authorization: Bearer {your_token}
```

## Rate Limiting

API requests are rate-limited to 60 requests per minute per IP address.

INTRO,
];
