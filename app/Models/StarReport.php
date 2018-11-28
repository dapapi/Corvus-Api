<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StarReport extends Model
{
    public function starable()
    {
        return $this->morphTo();
    }
}
