<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Models\Star;

class Work extends Model
{
    //作品类型
    const MOVIE = 1;//电影
    const TV_PLAY = 2;//电视剧
    const VARIETY_SHOW = 3;//综艺节目
    const NET_PLAY = 4;//网剧

    protected $fillable = [
        'name',
        'director',
        'release_time',
        'works_type',
        'role',
        'co_star',
        'star_id',
        'creator_id'
    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function star()
    {
        return $this->belongsTo(Star::class, 'star_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }
}
