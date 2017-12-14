<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends BaseModel
{
    use SoftDeletes;

    protected $attributes = [
        'is_active' => true
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
        return boolval($this->is_active);
    }
}
