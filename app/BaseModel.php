<?php

namespace App;

use App\Traits\Flushable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BaseModel extends Model
{
    use Flushable;
    use ValidationTrait;

    /**
     * Returns a model with the given UUID.
     *
     * @param $uuid
     *
     * @return mixed
     */
    public static function loadFromUuid($uuid)
    {
        $class = get_called_class();
        $model = new $class();
        $model->checkValid($uuid, $class);
        $classId = $model->getIdFromUuid($uuid, $model);

        $cacheKey = sprintf(
            '_get_base_model_class_uuid_%s_%s_%i_cache_',
            $uuid,
            $class,
            $classId
        );

        $result = Cache::tags([
            self::getTagFromClassName($class),
        ])->get($cacheKey);

        if (!$result) {
            $result = $class::where('id', $classId)->first();

            Cache::tags([
                self::getTagFromClassName($class),
            ])->put($cacheKey, $result, 600);
        }

        return $result;
    }

    protected static function getTagFromClassName(String $className) : String
    {
        $pieces = explode('\\', $className);
        $index = count($pieces);
        $result = strtolower(sprintf('%ss', $pieces[$index - 1] ?: 'cache'));
        $tag = Str::plural($result);
        return $tag;
    }

    /**
     * Get true ID from UUID for a given model.
     *
     * @param $uuid
     * @param $model
     *
     * @return mixed
     */
    private function getIdFromUuid($uuid, $model)
    {
        $classId = $model::where('_id', $uuid)->first()->id;

        return $classId;
    }
}
