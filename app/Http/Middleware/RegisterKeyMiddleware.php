<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Log;
use Closure;

class RegisterKeyMiddleware
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
        $registrationAccessKey = env('REGISTRATION_ACCESS_KEY');

        // Ensure that the requesting device has a registration access key
        if (!is_null($registrationAccessKey) && ($registrationAccessKey === $request->header('Registration-Access-Key'))) {
            return $next($request);
        }

        // It's important that all registration related errors say the same thing
        Log::error('Registration attempted without a registration key.');
        return response()->json(['error' => 'Missing Registration Key'], 401);
    }
}
