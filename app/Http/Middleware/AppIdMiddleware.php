<?php

namespace App\Http\Middleware;

use Closure;

class AppIdMiddleware
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $appId = env('APP_ID');

        // Ensure that the requesting app is legit
        if (!is_null($appId) && ($appId === $request->header('App'))) {
            return $next($request);
        }

        throw new \Exception('There was a problem validating the request.');
    }
}
