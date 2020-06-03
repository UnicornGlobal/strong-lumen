<?php

namespace App\Http\Middleware;

use App\Role;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param $requiredRole
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function handle($request, Closure $next, String $requiredRole)
    {
        if (empty(Auth::user())) {
            throw new Exception('User not logged in.');
        }

        $allRoles = explode('|', $requiredRole);

        foreach ($allRoles as $role) {
            $model = Role::where('name', $role)->first();

            if ((null !== $model) && $model->is_active && Auth::user()->hasRoleById($model->_id)) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'Incorrect Role'], 401);
    }
}
