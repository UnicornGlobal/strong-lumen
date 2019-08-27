<?php

namespace App\Http\Middleware;

use Closure;

class NoCache
{
    /**
     * Destroy all caches.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string                   $role
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->header('Cache-Control', 'no-cache, must-revalidate');

        return $response;
    }
}
