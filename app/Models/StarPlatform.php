<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StarPlatform extends Model
{
    /**
     * 获得拥有此明星平台的模型
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function platformable()
    {
        return $this->morphTo();
    }
}
