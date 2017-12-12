<?php

namespace App;

use App\Mail\PasswordResetMessage;
use function GuzzleHttp\Psr7\_parse_request_uri;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\SoftDeletes;

use Tymon\JWTAuth\Contracts\JWTSubject as AuthenticatableUserContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, AuthenticatableUserContract
{
    use Authenticatable, Authorizable, SoftDeletes, CanResetPassword;

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
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
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
     * @param  string  $token
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

    public function roles()
    {
        return $this->belongsToMany('App\Role', 'user_role')->using('App\UserRole')->withTimestamps();
    }

    public function hasRole($role){
        foreach($this->roles()->get() as $userRole){
            if($userRole->name === $role && $userRole->active === 1){
                return true;
            }
        }
        return false;
    }

    public function assignRole($role)
    {
        if (!$this->hasRole($role)) {
            $this->roles()->syncWithoutDetaching(
                [
                Role::where('name', $role)->first()->id
                    =>
                    [
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                    ]
                ]
            );
        }
    }

    public function revokeRole($role){
        if ($this->hasRole($role)) {
            $this->roles()->detach(
                Role::where('name', $role)->first()->id
            );
        }
    }
}
