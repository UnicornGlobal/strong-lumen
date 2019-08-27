<?php

return [
     /*
     | Laravel CORS
     |
     | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
     | to accept any value.
     |
     */
    'supportsCredentials' => true,
    'allowedOrigins'      => ['*'],
    'allowedHeaders'      => ['Content-Type', 'Content-Length', 'Origin', 'X-Requested-With', 'Debug-Token', 'Registration-Access-Key', 'X-CSRF-Token', 'App', 'User-Agent', 'Authorization'],
    'allowedMethods'      => ['GET', 'POST', 'PUT',  'DELETE', 'OPTIONS'],
    'exposedHeaders'      => ['Authorization'],
    'maxAge'              => 0,
];
