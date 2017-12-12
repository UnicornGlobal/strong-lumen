<?php

namespace App\Http\Middleware;

use App\Http\Controllers\UserController;
use App\Role;
use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

Class RolesMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param int $id
     * @param $role
     * @return mixed
     */
    public function handle($request, Closure $next, $role = null)
    {
        if(is_null($role)){
            if (Auth::user()->roles->count() != 0){
                return $next($request);
            }
        }

        //when implementing extra loop, add check for active role
        if(Auth::user()->hasRole($role)){
            return $next($request);
        }
        return response()->json(['error' => 'Incorrect Role'], 401);
    }
}