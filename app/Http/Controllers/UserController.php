<?php

namespace App\Http\Controllers;

use App\User;
use App\ValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Webpatser\Uuid\Uuid;

class UserController extends Controller
{
    use ValidationTrait;

    public function getUserById(Request $request, $userId)
    {
        if (!$userId) {
            throw new \Exception('There was a problem retrieving the user.');
        }

        $this->isValidUserID($userId);

        $user = User::where('_id', $userId)->first();

        return response()->json($user);
    }

    public function getSelf()
    {
        $userId = Auth::user()->_id;

        $user = User::where('_id', $userId)->first();

        return response()->json($user);
    }

    /**
     * Update user in the system
     */
    public function updateUserByUUID(Request $request, $userId)
    {
        $this->validate($request, [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
        ]);

        if (Auth::user()->_id !== $userId) {
            throw new \Exception('Illegal attempt to adjust another users details. The suspicious action has been logged.');
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

        return response('OK', 200);
    }

    public function changePassword(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required|string',
            'newpassword' => 'required|string|different:password',
        ]);

        $fields = $request->input();

        if (!isset($fields['newpassword'])) {
            throw new \Exception('There was a problem changing the password.');
        }

        $user = User::where('username', $fields['username'])->first();

        if (!Hash::check($request['password'], $user->password)) {
            throw new \Exception('There was a problem changing the password.');
        }

        $user->password = Hash::make($request['newpassword']);
        $user->updated_by = Auth::user()->id;
        $user->save();

        return response('OK', 200);
    }
}
