<?php

return [
    /*
    * You can enable CORS for 1 or multiple paths.
    * Example: ['api/*']
    */
    'paths' => [],

    /*
    * Matches the request method. `[*]` allows all methods.
    */
    'allowed_methods' => ['GET', 'POST', 'PUT',  'DELETE', 'OPTIONS'],

    /*
     * Matches the request origin. `[*]` allows all origins.
     */
    'allowed_origins' => ['*'],

    /*
     * Matches the request origin with, similar to `Request::is()`
     */
    'allowed_origins_patterns' => [],

    /*
     * Sets the Access-Control-Allow-Headers response header. `[*]` allows all headers.
     */
    'allowed_headers' => ['Content-Type', 'Content-Length', 'Origin', 'X-Requested-With', 'Debug-Token', 'Registration-Access-Key', 'X-CSRF-Token', 'App', 'User-Agent', 'Authorization'],

    /*
     * Sets the Access-Control-Expose-Headers response header.
     */
    'exposed_headers' => ['Authorization'],

    /*
     * Sets the Access-Control-Max-Age response header.
     */
    'max_age' => 0,

    /*
     * Sets the Access-Control-Allow-Credentials header.
     */
    'supports_credentials' => true,
];
