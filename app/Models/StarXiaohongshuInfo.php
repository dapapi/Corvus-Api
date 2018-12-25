<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StarXiaohongshuInfo extends Model
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
    public function stars()
    {
        return $this->belongsTo(Star::class,'star_id','id');
    }
    public function platforms()
    {
        return $this->belongsTo(Platform::class,'paltform_id','id');
    }
}
