<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmAccountMessage;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Routing\Controller as BaseController;
use Webpatser\Uuid\Uuid;

class RegistrationController extends BaseController
{
    private $requiredFields = [
        'username',
        'password',
        'firstName',
        'lastName',
        'email',
    ];

    public function registerEmail(Request $request)
    {
        $details = $request->only(
            'username',
            'password',
            'firstName',
            'lastName',
            'email'
        );

        $this->validate($request, [
            'username'  => 'required|string|unique:users',
            'password'  => 'required|string|min:8',
            'firstName' => 'required|string',
            'lastName'  => 'required|string',
            'email'     => 'required|email|distinct|unique:users',
        ]);

        $this->checkHasMinimum($details);

        try {
            $newUserId = $this->createUser($details);

            return response()->json(['_id' => $newUserId], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registration Failed'], 403);
        }
    }

    private function checkHasMinimum($details)
    {
        foreach ($this->requiredFields as $field) {
            if (is_null($details[$field])) {
                throw new \Exception('There was a problem validating the requested registration.');
            }
        }
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

        Mail::to($details['email'])->send(new ConfirmAccountMessage($newUser));

        DB::commit();

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

            return redirect(sprintf('%s/confirmed/%s', env('ADMIN_URL'), encrypt($otp)));
        } catch (\Exception $e) {
            header(sprintf('refresh:0; url=%s/login?invalidconfirmation=true', env('ADMIN_URL')));

            return false;
        }
    }

    //Assigning a role to the newly created user
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
        } catch (\Exception $e) {
            throw new \Exception('There was a problem assigning the role.');
        }
    }
}
