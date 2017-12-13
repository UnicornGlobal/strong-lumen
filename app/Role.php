<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'active' => true //Roles are active on creation
    ];

    protected $dates = ['deleted_at'];

    public function users()
    {
        return $this->belongsToMany('App\User', 'user_role')->withTimestamps()->using('App\UserRole');
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function isActive()
    {
        return $this->active;
    }
}
