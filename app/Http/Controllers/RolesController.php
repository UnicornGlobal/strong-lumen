<?php

namespace App\Http\Controllers;

use App\Role;
use App\User;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class RolesController extends Controller
{
    /**
     * Get Role from name
     *
     * @param $name
     * @return Role
     */
    public function getRole($name)
    {
        $role = Role::loadRoleFromName($name);
        return $role;
    }

    /**
     * Get all roles in the DB
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getRoles()
    {
        return Role::all();
    }


    /**
     * Create a new role
     *
     * @param $name
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function createRole(string $name)
    {
        $role = Role::where('name', $name)->first();
        if(preg_match("/[a-z]{4,}/", $name) && is_null($role)){
            $role = new Role();
            $role->name = $name;
            $role->_id = Uuid::generate(4);
            $role->is_active = true;
            $role->created_by = Auth::user()->id;
            $role->updated_by = Auth::user()->id;
            $role->save();
        }
        else{
            return response('Role name invalid', 500);
        }
    }

    /**
     * Delete a given role
     * Only if the role is not assigned to any users
     *
     * @param $name
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function deleteRole($name)
    {
        $role = $this->getRole($name);
        if ($role->users->isEmpty()) {
            $role->delete();
            return response(200, 'OK');
        }
        return response('Role has assigned users', 500);
    }


    /**
     * Deactivate a given role
     *
     * @param $name
     */
    public function deactivateRole($name)
    {
        $role = $this->getRole($name);
        $role->is_active = false;
        $role->save();
    }

    /**
     * Activate a given role
     *
     * @param $name
     */
    public function activateRole($name)
    {
        $role = $this->getRole($name);
        $role->is_active = true;
        $role->save();
    }

    /**
     * Get all the users assigned to a given role
     *
     * @param $name
     * @return mixed
     */
    public function getUsersForRole($name)
    {
        $role = $this->getRole($name);
        $users = $role->users;
        return $users;
    }
}
