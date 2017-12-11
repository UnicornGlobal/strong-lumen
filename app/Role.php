<?php

namespace App;


use Illuminate\Database\Eloquent\Model;

class Role extends Model
{

    public function users()
    {
        return $this->belongsToMany('App\User', 'user_role')->withTimestamps()->using('App\UserRole');
    }

    public function addRole($role){
        Role::save(['name' => $role]);
    }
}