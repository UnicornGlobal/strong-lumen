<?php

return [
    /*
     | Laravel CORS
     |
     | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
     | to accept any value.
     |
     */
    'allowed_origins'          => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers'          => [
        'Content-Type',
        'Content-Length',
        'Origin',
        'X-Requested-With',
        'Debug-Token',
        'Registration-Access-Key',
        'X-CSRF-Token',
        'App',
        'User-Agent',
        'Authorization'
    ],
    'allowed_methods'          => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS'
    ],
    'exposed_headers'          => ['Authorization'],
    'max_age'                  => 0,
    'paths'                    => ['*'],
    'supports_credentials'     => true,
];
