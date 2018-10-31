<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    const NORMAL = 1;
    const LISTED = 2;
    const TOP500 = 3;

    protected $fillable = [
        'company',
        'brand',
        'industry_id',      // 行业id
        'industry',         // 行业
        'grade',             // 级别
        'region_id',        // 地区三级，存最下级id
        'address',
        'principal_id',
        'creator_id',
        'size',             // 规模
        'keyman',           // 决策关键人
        'desc',
        'type',
        'status',
    ];

    protected $dates = ['deleted_at'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
}
