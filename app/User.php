<?php

namespace App;

use App\Mail\PasswordResetMessage;
use App\Traits\GeneratesUuid;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject as AuthenticatableUserContract;

class User extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    AuthenticatableUserContract
{
    use Authenticatable, Authorizable, SoftDeletes, CanResetPassword, GeneratesUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        '_id',
        'api_key',
        'username',
        'password',
        'first_name',
        'last_name',
        'email',
        'confirm_code',
        'confirmed_at',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'otp',
        'otp_created_at',
        'profile_picture_id',
        'pivot',
        'password',
        'remember_token',
        'id',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'is_banned',
        'api_key',
        'confirm_code',
        'confirmed_at',
        'deleted_at',
    ];

    protected $appends = [
        'confirmed',
    ];

    // Verification status
    public function getConfirmedAttribute()
    {
        return $this->attributes['confirmed'] = !is_null($this->confirmed_at);
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     *
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        Mail::to($this->email)->send(new PasswordResetMessage($this, $token));
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function profile_picture()
    {
        return $this->belongsTo('App\ProfilePicture', 'profile_picture_id');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role', 'user_role')->using('App\UserRole')->withTimestamps();
    }

    /**
     * Check if the user has a specified role.
     *
     * @param $role
     *
     * @return bool
     */
    public function hasRole($roleId)
    {
        foreach ($this->roles()->get() as $userRole) {
            if ($userRole->_id === $roleId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign a role to this user.
     *
     * @param $role
     */
    public function assignRole($roleId)
    {
        $role = Role::loadFromUuid($roleId);

        if (!empty($role)
            && !$this->hasRole($role->_id)) {
            $this->roles()->syncWithoutDetaching(
                [
                $role->id => [
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                    ],
                ]
            );
        }
    }

    /**
     * Remove a role from this user.
     *
     * @param $name
     */
    public function revokeRole($roleId)
    {
        $role = Role::loadFromUuid($roleId);
        if ($this->hasRole($roleId)) {
            $this->roles()->detach(
                $role->id
            );
        }
    }
}
