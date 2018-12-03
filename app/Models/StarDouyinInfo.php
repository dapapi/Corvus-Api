<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StarDouyinInfo extends Model
{
    protected $fillable = [
        'open_id',
        'url',
        'nickname',
        'avatar'
    ];
    public function starPlatforms()
    {
        return $this->morphMany(StarPlatform::class,'platformable');
    }
}
