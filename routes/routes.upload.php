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
                'uses' => 'UploadController@setUserProfilePicture',
            ]);

            // Private
            $router->post('/document', [
                'as'   => 'upload_document',
                'uses' => 'UploadController@uploadDocument',
            ]);
        });
    }
);

/*
 * This lives outside of the grouping because of middleware issues
 * on Storage::download calls.
 *
 * The response object doesn't have the headers() method, so it
 * cannot append the headers defined in the middlewares
 */
$router->get('/api/download/document/{documentId}', [
    'as'         => 'download.document',
    'middleware' => [
        'auth:api',
        'throttle',
        'cors',
    ],
    'uses' => 'UploadController@downloadDocument',
]);
