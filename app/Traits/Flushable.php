<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait Flushable
{
    /**
     * Flushes the current models cache tags
     */
    public static function bootFlushable()
    {
        $class = get_called_class();

        static::creating(function ($model) use ($class) {
            Cache::tags([
                self::getTagFromClassName($class),
            ])->flush();
        });

        static::updating(function ($model) use ($class) {
            Cache::tags([
                self::getTagFromClassName($class),
            ])->flush();
        });

        static::deleting(function ($model) use ($class) {
            Cache::tags([
                self::getTagFromClassName($class),
            ])->flush();
        });
    }

    /**
     * Allows any model to force a cache flush for itself
     */
    public function flush()
    {
        $class = get_called_class();

        Cache::tags([
            self::getTagFromClassName($class),
        ])->flush();
    }
}
