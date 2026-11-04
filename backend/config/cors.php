<?php

/*
| Allowed origins come from FRONTEND_URLS (comma-separated) so the Vercel
| domain can be added in Render's env vars without a code change.
*/

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_URLS', 'http://localhost:5173')),
)));

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'OPTIONS'],

    'allowed_origins' => $origins,

    // Optional, e.g. "#^https://propmap-[a-z0-9-]+\.vercel\.app$#" for preview deploys.
    'allowed_origins_patterns' => array_values(array_filter([env('FRONTEND_URL_PATTERN')])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
