<?php

require_once __DIR__.'/../vendor/autoload.php';

try {
    (Dotenv\Dotenv::create(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

$app->withFacades();

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//    App\Http\Middleware\ExampleMiddleware::class
// ]);

$app->routeMiddleware([
    'auth'       => App\Http\Middleware\Authenticate::class,
    'register'   => App\Http\Middleware\RegisterKeyMiddleware::class,
    'appid'      => App\Http\Middleware\AppIdMiddleware::class,
    'throttle'   => App\Http\Middleware\ThrottleRequests::class,
    'nocache'    => App\Http\Middleware\NoCache::class,
    'hideserver' => App\Http\Middleware\ServerHeader::class,
    'security'   => App\Http\Middleware\SecurityHeaders::class,
    'csp'        => App\Http\Middleware\ContentSecurityPolicyHeaders::class,
    'cors'       => \Barryvdh\Cors\HandleCors::class,
    'role'       => \App\Http\Middleware\RolesMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

// JWTs
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);

// CORS
$app->register(Barryvdh\Cors\ServiceProvider::class);

// Mail
$app->register(\Illuminate\Mail\MailServiceProvider::class);

// Password Reset
$app->register(\Illuminate\Auth\Passwords\PasswordResetServiceProvider::class);

// File System
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);
$app->configure('filesystems');

$app->configure('cors');

$app->configure('services');
$app->configure('mail');
$app->alias('mailer', Illuminate\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);

$app->make('queue');

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
    require __DIR__.'/../routes/routes.admin.php';
    require __DIR__.'/../routes/routes.upload.php';
});

return $app;
