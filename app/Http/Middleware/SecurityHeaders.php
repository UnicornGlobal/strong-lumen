<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    /**
     * Some common security headers.
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

        $response->header('X-Permitted-Cross-Domain-Policies', 'none');
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('Strict-Transport-Security', 'max-age=7776000; includeSubDomains');

        return $response;
    }
}
