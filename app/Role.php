<?php

namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;
    public function users()
    {
        return $this->belongsToMany('App\User', 'user_role')->withTimestamps()->using('App\UserRole');
    }

    public function getName(){
        return $this->name;
    }

    protected $dates = ['deleted_at'];
}