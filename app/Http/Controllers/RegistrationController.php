<?php

namespace App\Http\Controllers;

use App\Events\UserCreated;
use App\Role;
use App\User;
use App\ValidationTrait;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller as BaseController;
use Webpatser\Uuid\Uuid;

class RegistrationController extends BaseController
{
    use ValidationTrait;

    public function registerEmail(Request $request)
    {
        $this->validate($request, [
            'username'  => 'required|string|unique:users',
            'password'  => 'required|string|min:8',
            'firstName' => 'required|string',
            'lastName'  => 'required|string',
            'email'     => 'required|email|distinct|unique:users',
            'mobile'    => 'nullable|string',
            'location'  => 'nullable|string',
        ]);

        $details = $request->only(
            'username',
            'password',
            'firstName',
            'lastName',
            'email',
            'mobile',
            'location'
        );

        $newUserId = $this->createUser($details);

        return response()->json(['_id' => $newUserId], 201);
    }

    private function createUser($details)
    {
        DB::beginTransaction();

        $newUser = User::create([
            '_id'          => Uuid::generate(4)->string,
            'api_key'      => Uuid::generate(4)->string,
            'username'     => $details['username'],
            'password'     => Hash::make($details['password']),
            'first_name'   => $details['firstName'],
            'last_name'    => $details['lastName'],
            'email'        => $details['email'],
            'confirm_code' => Uuid::generate(4)->string,
            'created_by'   => 1,
            'updated_by'   => 1,
        ]);

        $this->addRole(Role::where('name', 'user')->first()->_id, $newUser);

        DB::commit();

        Cache::tags([
            'users',
        ])->flush();

        event(new UserCreated($newUser));

        return $newUser->_id;
    }

    public function confirmEmail($token): RedirectResponse
    {
        try {
            $user = User::where('confirm_code', $token)->first();

            if (!$user) {
                return redirect(sprintf('%s/login?invalidconfirmation=true', env('ADMIN_URL')));
            }

            if (null !== $user->confirmed_at) {
                return redirect(sprintf('%s/login?invalidconfirmation=true', env('ADMIN_URL')));
            }

            $user->otp = Uuid::generate(4)->string;
            $user->otp_created_at = Carbon::now();
            $user->confirmed_at = date('Y-m-d H:i:s');
            $user->save();

            Cache::tags([
                'users',
            ])->flush();

            return redirect(sprintf('%s/confirmed/%s', env('ADMIN_URL'), encrypt($user->otp)));
        } catch (\Exception $e) {
            header(sprintf('refresh:0; url=%s/login?invalidconfirmation=true', env('ADMIN_URL')));

            return false;
        }
    }

    // Assigning a role to the newly created user
    public function addRole($roleId, $newUser)
    {
        try {
            $role = Role::where('_id', $roleId)->first();
            $newUser->roles()->syncWithoutDetaching(
                [
                    $role->id => [
                        'created_by' => $newUser->id,
                        'updated_by' => $newUser->id,
                    ],
                ]
            );

            Cache::tags([
                'users',
                'roles',
            ])->flush();
        } catch (\Exception $e) {
            $this->throwValidationExceptionMessage('There was a problem assigning the role.');
        }
    }
}
