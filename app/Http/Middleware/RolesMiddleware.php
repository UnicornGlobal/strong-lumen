<?php

namespace App\Http\Middleware;

use App\Http\Controllers\UserController;
use App\Role;
use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class RolesMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param array $requiredRoles
     * @return mixed
     */
    public function handle($request, Closure $next, ...$requiredRoles)
    {
        if (empty($requiredRoles)) {
            if (Auth::user()->roles->count() !== 0) {
                return $next($request);
            }
        }

        foreach ($requiredRoles as $role) {
            if (empty(Role::where('name', $role)->first())) {
                return response()->json(['error' => 'Undefined role on route'], 500);
            }

            if (Role::where('name', $role)->first()->isActive()
                && Auth::user()->hasRole($role)) {
                return $next($request);
            }
        }
        return response()->json(['error' => 'Incorrect Role'], 401);
    }
}
