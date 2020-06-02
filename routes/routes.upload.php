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
            // Public
            $router->post('/profile', [
                'as'   => 'profile_picture',
                'uses' => 'ImageUploadController@setUserProfilePicture',
            ]);

            // Private
            $router->post('/document', [
                'as'   => 'upload_document',
                'uses' => 'DocumentUploadController@uploadDocument',
            ]);
        });
    }
);
