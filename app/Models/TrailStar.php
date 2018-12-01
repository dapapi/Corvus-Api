<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrailStar extends Model
{
    protected $table = 'trail_star';

    const EXPECTATION = 1;
    const RECOMMENDATION = 2;

    protected $fillable = [
        'trail_id',
        'starable_id',
        'starable_type',
        'type'
    ];

    public function starable()
    {
        return $this->morphto();
    }
}
