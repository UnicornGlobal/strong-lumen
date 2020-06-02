<?php

namespace App\Http\Controllers;

use App\Role;
use App\User;
use App\ValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ValidationTrait;

    /**
     * Get a user by their UUID.
     *
     * @param Request $request
     * @param $userId
     *
     * @throws \Exception
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserById($userId)
    {
        if (!$userId) {
            throw new \Exception('There was a problem retrieving the user.');
        }

        $this->isValidUserID($userId);

        $user = User::where('_id', $userId)->first();

        return response()->json($user);
    }

    /**
     * Returns model with current users UUID.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSelf()
    {
        $userId = Auth::user()->_id;

        $user = User::where('_id', $userId)->with('roles:_id,name')->first();

        return response()->json($user);
    }

    /**
     * Update user in the system.
     */
    public function updateUserByUUID(Request $request, $userId)
    {
        $this->validate($request, [
            'firstName' => 'required|string',
            'lastName'  => 'required|string',
        ]);

        if (Auth::user()->_id !== $userId) {
            throw new \Exception('Illegal attempt to adjust another users details. '.
                'The suspicious action has been logged.');
        }

        $this->isValidUserID($userId);

        $fields = $request->only([
            'firstName',
            'lastName',
        ]);

        $user = Auth::user();

        if (!is_null($fields['firstName'])) {
            $user->first_name = $fields['firstName'];
        }

        if (!is_null($fields['lastName'])) {
            $user->last_name = $fields['lastName'];
        }

        $user->updated_by = Auth::user()->id;
        $user->save();

        Cache::tags([
            'users',
        ])->flush();

        return response('OK', 200);
    }

    /**
     * Change the users password.
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function changePassword(Request $request)
    {
        $this->validate($request, [
            'username'    => 'required|string',
            'password'    => 'required|string',
            'newpassword' => 'required|string|different:password',
        ]);

        $fields = $request->input();

        $user = User::where('username', $fields['username'])->first();

        if (!Hash::check($request['password'], $user->password)) {
            throw new \Exception('There was a problem changing the password.');
        }

        $user->password = Hash::make($request['newpassword']);
        $user->updated_by = Auth::user()->id;
        $user->save();

        Cache::tags([
            'users',
        ])->flush();

        return response('OK', 200);
    }

    /**
     * Get the roles of a user.
     *
     * @param $userId
     *
     * @return mixed
     */
    public function getUserRoles($userId)
    {
        $user = User::loadFromUuid($userId);

        return $user->roles;
    }

    /**
     * Assign a new role to a user.
     *
     * @param $userId
     * @param $role
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function assignRole($roleId, $userId)
    {
        User::loadFromUuid($userId)->assignRole($roleId);

        Cache::tags([
            'users',
        ])->flush();

        return response('OK', 200);
    }

    /**
     * Revoke a role from a user.
     *
     * @param $userId
     * @param $role
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function revokeRole($roleId, $userId)
    {
        User::loadFromUuid($userId)->revokeRole($roleId);

        Cache::tags([
            'users',
        ])->flush();

        return response('OK', 200);
    }

    public function getAllUsers()
    {
        $users = User::with([
            'roles:_id,name',
            'profile_picture'
        ])->get();

        return response()->json(compact('users'));
    }

    public function deleteUser($userId)
    {
        $user = User::loadFromUuid($userId);

        if ($user->roles->count() === 1 &&
            $user->hasRole(Role::where('name', 'user')->first()->_id)) {
            $user->delete();

            Cache::tags([
                'users',
            ])->flush();

            return response()->json(['success' => true], 202);
        }

        return response()->json(['error' => 'User has a role other than \'user\', cannot delete'], 404);
    }
}
