<?php

namespace App\Http\Controllers;


use App\User;

class RolesController extends Controller
{
    public function getRoles($id)
    {
        return User::where('id', $id)->first()->roles;
    }

    public function assignRole($id, $role)
    {
        User::where('id', $id)->first()->assignRole($role);
        return response('OK', 200);
    }

    public function revokeRole($id, $role)
    {
        User::where('id', $id)->first()->revokeRole($role);
        return response('OK', 200);
    }

    public function createRole($name){
        Role::save();
    }
}