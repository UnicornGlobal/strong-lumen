<?php

namespace App\Http\Controllers;


use App\User;

class RolesController extends Controller
{
    public function getRoles($id)
    {
        return User::where('id', $id)->first()->roles;
    }

    public function addRole($id, $role)
    {
        User::where('id', $id)->first()->addRole($role);
        return response('OK', 200);
    }

    public function removeRole($id, $role)
    {
        User::where('id', $id)->first()->removeRole($role);
        return response('OK', 200);
    }
}