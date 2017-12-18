<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends BaseModel
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function users()
    {
        return $this->belongsToMany('App\User', 'user_role')->withTimestamps()->using('App\UserRole');
    }
}
