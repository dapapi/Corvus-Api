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

    // 销售进展 用progress_status字段
    const STATUS_UNCONFIRMED = 1;
    const STATUS_CONFIRMED = 2;
    const STATUS_DELETE = 3;
    const STATUS_REFUSE = 0;

    // 线索状态 用status字段
    const PROGRESS_BEGIN= 1;
    const PROGRESS_REFUSE = 2;
    const PROGRESS_CANCEL= 3;
    const PROGRESS_TALK = 4;
    const PROGRESS_INTENTION = 5;
    const PROGRESS_SIGNING = 6;
    const PROGRESS_SIGNED = 7;
    const PROGRESS_EXECUTE = 8;
    const PROGRESS_EXECUTING = 9;
    const PROGRESS_EXECUTED = 10;
    const PROGRESS_PAYBACK = 11;
    const PROGRESS_FEEDBACK = 12;

    //
    const TYPE_MOVIE = 1; // 影视项目
    const TYPE_VARIETY = 2; // 综艺项目
    const TYPE_ENDORSEMENT = 3; // 商务代言
    const TYPE_PAPI = 4; // papi项目
    const TYPE_BASE = 5; // 基础项目

    // priority 优先级
    const PRIORITY_C = 1;
    const PRIORITY_B = 2;
    const PRIORITY_A = 3;
    const PRIORITY_S = 4;


    protected $fillable = [
        'title',
        'brand',
        'industry_id',      // 行业id
        'principal_id',
        'client_id',
        'contact_id',
        'creator_id',
        'type',
        'status',
        'priority',
        'cooperation_type',
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
        return $this->belongsTo(Contact::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }



    public function expectations()
    {
        return $this->morphedByMany(Star::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::EXPECTATION);
    }

    public function bloggerExpectations()
    {
        return $this->morphedByMany(Blogger::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::EXPECTATION);
    }

    public function recommendations()
    {
        return $this->morphedByMany(Star::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::RECOMMENDATION);
    }

    public function bloggerRecommendations()
    {
        return $this->morphedByMany(Blogger::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::RECOMMENDATION);
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_id', 'id');
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'resourceable','task_resources');
    }

    public function project()
    {
        return $this->hasOne(Project::class, 'trail_id','id');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }
}
