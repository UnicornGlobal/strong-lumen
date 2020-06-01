<?php

namespace App\Traits;

use Webpatser\Uuid\Uuid;

trait GeneratesUuid
{
    public static function bootGeneratesUuid()
    {
        static::creating(function ($model) {
            if (empty($model->_id)) {
                $model->_id = Uuid::generate(4)->string;
            }
        });
    }
}
