<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use ValidationTrait;

    public static function loadFromUuid($uuid)
    {
        $class  = get_called_class();
        $model = new $class;
        $model->checkValid($uuid, $class);
        $id = self::getIdFromUuid($uuid, $model);
        return $class::where('id', $id)->first();
    }

    public static function loadRoleFromName($name)
    {
        $id = self::getRoleIdFromName($name);
        $role = Role::where('id', $id)->first();
        return $role;
    }

    private static function getIdFromUuid($uuid, $model)
    {
        $id = $model::where('_id', $uuid)->first()->id;
        return $id;
    }

    public static function getRoleIdFromName($name)
    {
        $class  = get_called_class();
        $model = new $class;
        $model->checkEmptyName($name);
        $model->checkNameExists($name, $class);

        $id = $model::where('name', $name)->first()->id;
        return $id;
    }

    public function checkNameExists($name, $class)
    {
        if (!$class::where('name', $name)->first()) {
            $this->throwExceptionMessage('Invalid', $class);
        }
    }

    public function checkEmptyName($name)
    {
        if (empty($name || !isset($name) || is_null($name))) {
            $this->throwExceptionMessage('Empty', $name);
        }
    }
}
