<?php

$router->group(
    [
        'prefix' => 'api',
        'middleware' => ['nocache', 'hideserver', 'security', 'csp', 'cors', 'auth:api', 'throttle', 'role:admin'],
    ],
    function () use ($router) {
        /*
        * Admin Routes
        *
        */
        $router->group(['as' => 'admin', 'prefix' => 'admin'], function ($router) {
            //users
            $router->group(['prefix' => 'users', 'as' => 'users'], function ($router) {
                $router->get('/', ['as' => 'all', 'uses' => 'UserController@getAllUsers']);
                $router->delete('/{userId}', 'UserController@deleteUser');
                $router->get('/{id}/roles', 'UserController@getUserRoles');
                $router->post('/{id}/roles/assign/{roleId}', 'UserController@assignRole');
                $router->post('/{id}/roles/revoke/{roleId}', 'UserController@revokeRole');
            });

            //roles
            $router->group(['prefix' => 'roles'], function ($router) {
                $router->get('/{roleId}/users', 'RolesController@getUsersForRole');
                $router->get('/{roleId}', 'RolesController@getRole');
                $router->get('/', 'RolesController@getRoles');
                $router->post('/{roleId}', 'RolesController@createRole');
                $router->delete('/{roleId}', 'RolesController@deleteRole');
                $router->post('/{roleId}/activate', 'RolesController@activateRole');
                $router->post('/{roleId}/deactivate', 'RolesController@deactivateRole');
            });
        });
    }
);
