<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trail extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    // 线索来源类型
    const PERSONAL = 1;
    const MAIL = 2;
    const SENIOR = 3;

    const STATUS_NORMAL = 1;
    const STATUS_FROZEN = 2;

    const PROGRESS_FIRST = 1;

    protected $fillable = [
        'title',
        'brand',
        'principal_id',
        'client_id',
        'artist_id',
        'contact_id',
        'creator_id',
        'type',
        'status',
        'lock_status',
        'progress_status',
        'resource',
        'resource_type',
        'fee',
        'desc',
    ];

    protected $dates = ['deleted_at'];

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'contact_id', 'id');
    }

    public function expectations()
    {
        return $this->belongsToMany(Star::class, 'trail_star')->wherePivot('type', TrailStar::EXPECTATION);
    }

    public function recommendations()
    {
        return $this->belongsToMany(Star::class, 'trail_star')->wherePivot('type', TrailStar::RECOMMENDATION);
    }
}
