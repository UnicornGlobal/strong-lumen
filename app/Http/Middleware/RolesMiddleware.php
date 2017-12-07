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
    public function handle($request, Closure $next, $role)
    {
        if(Auth::user()->hasRole($role)){
            return $next($request);
        }
    }
}