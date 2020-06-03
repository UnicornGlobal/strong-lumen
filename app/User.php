<?php

namespace App;

use App\Mail\PasswordResetMessage;
use App\Traits\GeneratesUuid;
use App\ValidationTrait;
use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject as AuthenticatableUserContract;

class User extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    AuthenticatableUserContract
{
    use Authenticatable;
    use Authorizable;
    use SoftDeletes;
    use CanResetPassword;
    use GeneratesUuid;
    use ValidationTrait;

    protected $with = [
        'profile_picture',
    ];

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
        Mail::to($this->email)->queue(new PasswordResetMessage($this, $token));
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

    public function documents()
    {
        return $this->hasMany('App\Document');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role', 'user_role')->using('App\UserRole')->withTimestamps();
    }

    /**
     * Check if the user has a specified role by _id.
     *
     * @param $role
     *
     * @return bool
     */
    public function hasRoleById(String $roleId) : Bool
    {
        $cacheKey = sprintf(
            '_user_m_has_role_id_u_%s_r_%s_',
            $this->_id,
            $roleId
        );

        $result = Cache::tags([
            'users',
            'roles',
        ])->get($cacheKey);

        if ($result) {
            return $result;
        }

        foreach ($this->roles()->get() as $userRole) {
            if ($userRole->_id === $roleId) {
                Cache::tags([
                    'users',
                    'roles',
                ])->put($cacheKey, true);

                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user has a specified role by name.
     *
     * @param $role
     *
     * @return bool
     */
    public function hasRoleByName(String $role) : Bool
    {
        $cacheKey = sprintf(
            '_user_has_role_name_u_%s_r_%s_',
            $this->_id,
            $role
        );

        $result = Cache::tags([
            'users',
            'roles',
        ])->get($cacheKey);

        if ($result) {
            return $result;
        }

        foreach ($this->roles as $userRole) {
            if ($userRole->name === $role) {
                Cache::tags([
                    'users',
                    'roles',
                ])->put($cacheKey, true);

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

        if ($this->hasRoleByName('system')) {
            $this->throwValidationExceptionMessage('Cannot add roles to a system user');
        }

        if ($role->name === 'system') {
            $this->throwValidationExceptionMessage('Cannot assign the system role');
        }

        if ($role->name === 'user') {
            $this->throwValidationExceptionMessage('Cannot assign the user role');
        }

        if ($role->name === 'admin' && !Auth::user()->hasRoleByName('admin')) {
            $this->throwValidationExceptionMessage('Only admins can assign the admin role');
        }

        if (!empty($role)
            && !$this->hasRoleById($role->_id)) {
            $this->roles()->syncWithoutDetaching(
                [
                    $role->id => [
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                    ],
                ]
            );
        }

        Cache::tags([
            'users',
            'roles',
        ])->flush();
    }

    /**
     * Remove a role from this user.
     *
     * @param $name
     */
    public function revokeRole($roleId)
    {
        if ($this->hasRoleByName('system')) {
            $this->throwValidationExceptionMessage('Cannot revoke roles from the system user');
        }

        $role = Role::loadFromUuid($roleId);

        if ($role->name === 'system') {
            $this->throwValidationExceptionMessage('Cannot revoke the system role');
        }

        if ($role->name === 'user') {
            $this->throwValidationExceptionMessage('Cannot revoke the user role');
        }

        if ($role->name === 'admin' && !Auth::user()->hasRoleByName('admin')) {
            $this->throwValidationExceptionMessage('Only admins can revoke the admin role');
        }

        if ($this->hasRoleById($roleId)) {
            $this->roles()->detach(
                $role->id
            );
        }

        Cache::tags([
            'users',
            'roles',
        ])->flush();
    }
}
