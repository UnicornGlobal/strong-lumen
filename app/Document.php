<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends BaseModel
{
    use SoftDeletes;

    public $timestamps = true;

    protected $hidden = [
        'id',
        'file_key',
        'user_id',
    ];

    protected $visible = [
        '_id',
        'file_url',
        'title',
        'mime',
        'url',
    ];

    protected $fillable = [
        '_id',
        'user_id',
        'title',
        'file_url',
        'file_key',
        'mime',
        'created_by',
        'updated_by',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function user()
    {
        return $this->hasOne('App\User');
    }
}
