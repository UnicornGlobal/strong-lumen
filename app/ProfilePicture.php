<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProfilePicture extends BaseModel
{
    use SoftDeletes;

    public $timestamps = true;

    protected $hidden = [
        'id',
        'user_id',
        'file_key',
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

    protected $appends = [
        'url',
    ];

    public function getUrlAttribute(): string
    {
        if ($this->file_url) {
            if (preg_match('/^https?:\/\//', $this->file_url)) {
                return sprintf('%s', $this->file_url);
            }

            return sprintf('%s%s', env('API_URL'), $this->file_url);
        }
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
