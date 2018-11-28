<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Platform extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'logo', 'active_logo', 'url',
    ];
    public function starPlatform()
    {
        return $this->hasMany(StarPlatform::class);
    }
    public function starReports()
    {
        return $this->hasMany(StarReport::class);
    }
}
