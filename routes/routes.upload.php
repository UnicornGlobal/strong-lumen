<?php

$router->group(
    [
        'prefix'     => 'api',
        'middleware' => [
            'nocache',
            'hideserver',
            'security',
            'csp',
            'cors',
            'auth:api',
            'throttle',
        ],
    ],
    function () use ($router) {
        $router->group([
            'as'     => 'upload',
            'prefix' => 'upload',
        ], function ($router) {
            $router->post('/profile', [
                'as'   => 'profile_picture',
                'uses' => 'ImageUploadController@setUserProfilePicture',
            ]);
        });
    }
);
