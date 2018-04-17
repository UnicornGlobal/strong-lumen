<?php

namespace App\Http\Middleware;

use App\Role;
use Closure;
use Illuminate\Support\Facades\Auth;

class RolesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param $requiredRole
     * @return mixed
     */
    public function handle($request, Closure $next, $requiredRole)
    {
        $model = Role::where('name', $requiredRole)->first();
        if (empty(Auth::user())) {
            throw new \Exception('User not logged in.');
        }

        if (!is_null($model) && $model->is_active && Auth::user()->hasRole($model->_id)) {
            return $next($request);
        }

        return response()->json(['error' => 'Incorrect Role'], 401);
    }
}
