<?php

namespace App\Http\Middleware;

use Closure;

class ContentSecurityPolicyHeaders
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

        $response->header('Content-Security-Policy', "default-src 'none', connect-src 'self', 'upgrade-insecure-requests';");

        return $response;
    }
}
