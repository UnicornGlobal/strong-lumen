<?php

namespace App\Providers;

use App\Events\UserCreated;
use App\Listeners\OnUserCreated;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\UserCreated' => [
            'App\Listeners\OnUserCreated',
        ],
    ];
}
