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
            if (!Auth::user()->roles->isEmpty()) {
                return $next($request);
            }
        }

        foreach ($requiredRoles as $role) {
            $model = Role::where('name', $role)->first();

            if (!is_null($model) && $model->is_active && Auth::user()->hasRole($model->_id)) {
                return $next($request);
            }
        }
        return response()->json(['error' => 'Incorrect Role'], 401);
    }
}
