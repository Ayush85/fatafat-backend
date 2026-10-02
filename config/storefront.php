<?php

return [

    // Shared secret the admin panel sends (X-Storefront-Secret) when it asks this
    // API to drop cached data. Empty disables the endpoint.
    'invalidate_secret' => env('STOREFRONT_INVALIDATE_SECRET'),

    // Next.js storefront /api/revalidate endpoint and its secret.
    'revalidate_url' => env('STOREFRONT_REVALIDATE_URL', rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/') . '/api/revalidate'),
    'revalidate_secret' => env('STOREFRONT_REVALIDATE_SECRET'),

];
