<?php

namespace App\Http\Controllers;

use App\Role;
use App\User;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class RolesController extends Controller
{
    public function getRole($name)
    {
        $role = Role::loadRoleFromName($name);
        return $role;
    }

    public function getRoles()
    {
        return Role::all();
    }

    public function createRole($name)
    {
        $role = new Role();
        $role->name = $name;
        $role->_id = Uuid::generate(4);
        $role->is_active = true;
        $role->created_by = Auth::user()->id;
        $role->updated_by = Auth::user()->id;
        $role->save();
    }

    public function deleteRole($name)
    {
        $role = $this->getRole($name);
        if ($role->users->isEmpty()) {
            $role->delete();
            return response(200, 'OK');
        }
        return response(500, 'Role has assigned users');
    }

    public function deactivateRole($name)
    {
        $role = $this->getRole($name);
        $role->is_active = false;
        $role->save();
    }

    public function activateRole($name)
    {
        $role = $this->getRole($name);
        $role->is_active = true;
        $role->save();
    }

    public function getUsersForRole($name)
    {
        $role = $this->getRole($name);
        $users = $role->users;
        return $users;
    }
}
