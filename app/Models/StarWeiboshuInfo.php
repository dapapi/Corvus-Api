<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StarWeiboshuInfo extends Model
{
    protected $table='star_weibo_infos';
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
