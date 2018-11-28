<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StarWeiboshuInfo extends Model
{
    public function starPlatforms()
    {
        return $this->morphMany(StarPlatform::class,'platformable');
    }
}
