<?php

namespace App\Models;

use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use App\Traits\OperateLogTrait;
use App\Traits\SearchDataTrait;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class Project extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }
    use OperateLogTrait;

    const TYPE_MOVIE = 1; // 影视项目
    const TYPE_VARIETY = 2; // 综艺项目
    const TYPE_ENDORSEMENT = 3; // 商务代言
    const TYPE_PAPI = 4; // papi项目
    const TYPE_BASE = 5; // 基础项目

    const STATUS_NORMAL = 1; // 进行中
    const STATUS_COMPLETE = 2; // 完成
    const STATUS_FROZEN = 3; // 终止
    const STATUS_DEL = 4; // 删除
    const PROJECT_TYPE = 'projects'; // 业务类型


    protected $fillable = [
        'title',
        'project_number',
        'principal_id',
        'creator_id',
        'trail_id',
        'privacy',
        'priority',
        'projected_expenditure',
        'status',
        'type',
        'desc',
        'start_at',
        'end_at',
        'complete_at',
        'stop_at',
        'delete_at'
    ];

//    protected static function boot()
//    {
//        parent::boot(); // TODO: Change the autogenerated stub
//        static::addGlobalScope(new SearchDataScope());
//    }
    public function scopeSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $rules = (new ScopeRepository())->getDataViewUsers();
        return (new SearchDataScope())->getCondition($query, $rules, $userid)->orWhere(DB::raw("{$userid} in (
            select u.id from projects as p 
            left join module_users as mu on mu.moduleable_id = p.id and 
            mu.moduleable_type='" . ModuleableType::PROJECT .
            "' left join users as u on u.id = mu.user_id where p.id = projects.id
        )"));
    }

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function participants()
    {
        return $this->morphToMany(User::class, 'moduleable', 'module_users')->wherePivot('type', ModuleUserType::PARTICIPANT);
    }

    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }


    public function fields()
    {
        return $this->hasMany(FieldValue::class);
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'resourceable','task_resources');
    }

    public function trail()
    {
        return $this->belongsTo(Trail::class);
    }

    public function relateProjects()
    {
        return $this->morphedByMany(Project::class, 'moduleable', 'project_relates');
    }

    public function relateTasks()
    {
        return $this->morphedByMany(Task::class, 'moduleable', 'project_relates');
    }
}
