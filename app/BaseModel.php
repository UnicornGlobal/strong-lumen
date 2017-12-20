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
        $classId = $model->getIdFromUuid($uuid, $model);
        return $class::where('id', $classId)->first();
    }

    /**
     * Get true ID from UUID for a given model
     *
     * @param $uuid
     * @param $model
     * @return mixed
     */
    private function getIdFromUuid($uuid, $model)
    {
        $classId = $model::where('_id', $uuid)->first()->id;
        return $classId;
    }
}
