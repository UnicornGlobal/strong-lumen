<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/*
 * Includes a set of security-focused middeware
 *
 * You can see more info on each of them in the Http/Middleware folder
 *
 * Feel free to add/edit/remove Middlewares
 */
$router->group(
    [
        'prefix'     => '',
        'middleware' => ['nocache', 'hideserver', 'security', 'csp', 'cors'],
    ],
    function () use ($router) {

    /*
     * Routes that do not require a JWT
     *
     * Different routes have different combinations based on use case.
     */

        /*
         *  Ensures that retrieving config is allowed with the correct app id
         *
         *  Ensure APP_ID in your .env
         *  Request with `App: your-key-here`
         */
        $router->group(['middleware' => ['throttle:10,1', 'appid']], function () use ($router) {
            $router->get('/config/app', 'ConfigController@getAppConfig');
        });

        /*
         *  Ensures that registration is only possible if you know the token.
         *
         *  Ensure REGISTRATION_ACCESS_KEY in your .env
         *  Request with `Registration-Access-Key: your-key-here`
         */
        $router->group(
            [
                'prefix'     => 'register',
                'middleware' => ['register', 'throttle:3,1'],
            ],
            function () use ($router) {
                $router->post('/email', 'RegistrationController@registerEmail');
            }
        );

        /*
         * 10 Login and Logouts per minute
         */
        $router->group(['middleware' => 'throttle:10,1'], function () use ($router) {
            $router->post('/login', 'AuthController@postLogin');
            $router->post('/logout', 'AuthController@logout');
        });

        /*
         * Only allow x of these requests per minute.
         *
         * Production should be a low number
         */
        $router->group(['middleware' => 'throttle:10,1'], function () use ($router) {
            $router->get('/confirm/{token}', 'RegistrationController@confirmEmail');
            $router->post('/reset', 'ResetController@postEmail');
            $router->post('/reset/{token}', 'ResetController@postReset');
        });

        $router->group(['prefix' => 'roles', 'middleware' => ['role:admin']], function () use ($router) {
            $router->get('/{roleId}/users', 'RolesController@getUsersForRole');
            $router->get('/{roleId}', 'RolesController@getRole');
            $router->get('/', 'RolesController@getRoles');
            $router->post('/{roleId}', 'RolesController@createRole');
            $router->delete('/{roleId}', 'RolesController@deleteRole');
            $router->post('/{roleId}/activate', 'RolesController@activateRole');
            $router->post('/{roleId}/deactivate', 'RolesController@deactivateRole');
        });

        $router->group(['prefix' => 'users', 'middleware' => ['role:admin']], function () use ($router) {
            $router->get('/{id}/roles', 'UserController@getUserRoles');
            $router->post('/{id}/roles/assign/{roleId}', 'UserController@assignRole');
            $router->post('/{id}/roles/revoke/{roleId}', 'UserController@revokeRole');
        });

        /*
         * What you set this throttle to depends on your use case.
         * JWT refresh
         */
        $router->group(['middleware' => ['jwt.refresh', 'throttle:10,1']], function () use ($router) {
            $router->post('/refresh', 'AuthController@refresh');
        });

        /*
         * Authenticated Routes
         */
        $router->group(['prefix' => 'api', 'middleware' => ['auth:api', 'throttle']], function () use ($router) {
            $router->get('/', function () use ($router) {
                return $router->app->version();
            });

            /*
             * Users
             */
            $router->get('/me', 'UserController@getSelf');
            $router->post('/users/change-password', 'UserController@changePassword');
            $router->get('/users/{userId}', 'UserController@getUserById');
            $router->post('/users/{userId}', 'UserController@updateUserByUUID');
        });
    }
);
