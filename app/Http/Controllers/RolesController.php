<?php

namespace App\Http\Controllers;

use App\Role;
use App\ValidationTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Webpatser\Uuid\Uuid;

class RolesController extends Controller
{
    use ValidationTrait;

    /**
     * Get Role from name.
     *
     * @param $name
     *
     * @return Role
     */
    public function getRole($roleId)
    {
        $role = Role::loadFromUuid($roleId);

        return $role;
    }

    /**
     * Get all roles in the DB.
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getRoles()
    {
        return Role::all();
    }

    /**
     * Create a new role.
     *
     * @param $name
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function createRole(string $name)
    {
        $role = Role::where('name', $name)->first();
        if (preg_match('/[a-z]{4,}/', $name) && is_null($role)) {
            $role = new Role();
            $role->name = $name;
            $role->_id = Uuid::generate(4)->string;
            $role->is_active = true;
            $role->created_by = Auth::user()->id;
            $role->updated_by = Auth::user()->id;
            $role->save();

            Cache::tags([
                'users',
                'roles',
            ])->flush();

            return response()->json(['_id' => $role->_id]);
        }

        $this->throwValidationExceptionMessage('Role name invalid');
    }

    /**
     * Delete a given role
     * Only if the role is not assigned to any users.
     *
     * @param $name
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function deleteRole($roleId)
    {
        $role = $this->getRole($roleId);
        if ($role->users->isEmpty()) {
            $role->delete();

            Cache::tags([
                'users',
                'roles',
            ])->flush();

            return response()->json(['success' => true]);
        }

        $this->throwValidationExceptionMessage('Role has assigned users');
    }

    /**
     * Deactivate a given role.
     *
     * @param $name
     */
    public function deactivateRole($roleId)
    {
        $role = $this->getRole($roleId);
        $role->is_active = false;
        $role->save();

        Cache::tags([
            'users',
            'roles',
        ])->flush();
    }

    /**
     * Activate a given role.
     *
     * @param $name
     */
    public function activateRole($roleId)
    {
        $role = $this->getRole($roleId);
        $role->is_active = true;
        $role->save();

        Cache::tags([
            'users',
            'roles',
        ])->flush();
    }

    /**
     * Get all the users assigned to a given role.
     *
     * @param $name
     *
     * @return mixed
     */
    public function getUsersForRole($roleId)
    {
        $role = $this->getRole($roleId);
        $users = $role->users;

        return $users;
    }
}
