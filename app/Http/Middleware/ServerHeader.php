<?php

namespace App\Http\Middleware;

use Closure;

class ServerHeader
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
        $response = $next($request);

        $version = env('API_VERSION', 'dev');

        $response->header('Server', 'Uniqode Services (staging)');
        $response->header('X-Powered-By', 'Uniqode Services (staging)');

        if (app()->environment('production')) {
            $response->header('Server', sprintf('Uniqode Services (%s)', $version));
            $response->header('X-Powered-By', sprintf('Uniqode Services (%s)', $version));
        }

        return $response;
    }
}
