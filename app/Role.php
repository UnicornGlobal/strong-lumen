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

    /**
     * Load the model from a given name
     *
     * @param $name
     * @return mixed
     */
    public static function loadRoleFromName($name)
    {
        $id = self::getRoleIdFromName($name);
        $role = Role::where('id', $id)->first();
        return $role;
    }

    /**
     * Find true ID from name, with validation
     *
     * @param $name
     * @return mixed
     */
    public static function getRoleIdFromName($name)
    {
        $class  = get_called_class();
        $model = new $class;
        $model->checkEmptyName($name);
        $model->checkNameExists($name, $class);

        $id = $model::where('name', $name)->first()->id;
        return $id;
    }
}
