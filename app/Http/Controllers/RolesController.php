<?php

namespace App\Http\Controllers;


use App\Role;
use App\User;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

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
        $role = new Role();
        $role->name = $name;
        $role->_id = Uuid::generate(4);
        $role->created_by = Auth::user()->id;
        $role->updated_by = Auth::user()->id;
        $role->save();
    }
}