<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use ValidationTrait;

    /**
     * Returns a model with the given UUID
     *
     * @param $uuid
     * @return mixed
     */
    public static function loadFromUuid($uuid)
    {
        $class  = get_called_class();
        $model = new $class;
        $model->checkValid($uuid, $class);
        $id = self::getIdFromUuid($uuid, $model);
        return $class::where('id', $id)->first();
    }

    /**
     * Get true ID from UUID for a given model
     *
     * @param $uuid
     * @param $model
     * @return mixed
     */
    private static function getIdFromUuid($uuid, $model)
    {
        $id = $model::where('_id', $uuid)->first()->id;
        return $id;
    }

    /**
     * Validate the given name exists in the DB
     *
     * @param $name
     * @param $class
     * @throws \Exception
     */
    public function checkNameExists($name, $class)
    {
        if (!$class::where('name', $name)->first()) {
            $this->throwExceptionMessage('Invalid', $class);
        }
    }

    /**
     * Ensure $name is not empty
     *
     * @param $name
     * @throws \Exception
     */
    public function checkEmptyName($name)
    {
        if (empty($name || !isset($name) || is_null($name))) {
            $this->throwExceptionMessage('Empty', $name);
        }
    }
}
