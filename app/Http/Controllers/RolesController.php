<?php

namespace App\Http\Controllers;


use App\Role;
use App\User;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class RolesController extends Controller
{
    public function getUserRoles($id)
    {
        return User::where('id', $id)->first()->roles;
    }

    public function getRole($name)
    {
        return Role::where('name', $name)->first();
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
        $role->active = true;
        $role->created_by = Auth::user()->id;
        $role->updated_by = Auth::user()->id;
        $role->save();
    }

    public function deleteRole($name){
        Role::where('name', $name)->delete();
    }

    public function deactivateRole($name){
        $role = Role::where('name', $name)->first();
        $role->active = false;
        $role->save();
    }

    public function activateRole($name){
        $role = Role::where('name', $name)->first();
        $role->active = true;
        $role->save();
    }
}