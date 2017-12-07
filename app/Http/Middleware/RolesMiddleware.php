<?php

namespace App\Http\Middleware;

use App\Http\Controllers\UserController;
use App\Role;
use App\User;
use Closure;

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
    public function handle($request, Closure $next, int $id, $role)
    {
        $user = new UserController();
       if(!isNull($request->id) && App->user()::roles->
    }
}