<?php

namespace App;

use App\Traits\GeneratesUuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Role extends BaseModel
{
    use GeneratesUuid;
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $hidden = [
        'pivot',
        'id',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function users()
    {
        return $this->belongsToMany('App\User', 'user_role')->withTimestamps()->using('App\UserRole');
    }

    public static function getByName(string $name)
    {
        $cacheKey = sprintf(
            '_get_role_by_name_%s',
            $name
        );

        $result = Cache::tags([
            'roles',
        ])->get($cacheKey);

        if (!$result) {
            $result = self::query()->where('name', $name)->first();

            Cache::tags([
                'roles',
            ])->put($cacheKey, $result);
        }

        return $result;
    }
}
