<?php

namespace App\Models;

use App\ModuleableType;
use App\ModuleUserType;
use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use App\Traits\OperateLogTrait;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Task extends Model
{
    use SoftDeletes;
    use OperateLogTrait;
    private $model_dic_id = DataDictionarie::TASK;//数据字典中模块id
    protected $fillable = [
        'title',
        'type_id',
        'task_pid',
        'creator_id',
        'principal_id',
        'status',
        'priority',
        'desc',
        'privacy',
        'start_at',
        'end_at',
        'complete_at',
        'stop_at',
        'deleted_at',
    ];
    public function scopeSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $rules = (new ScopeRepository())->getDataViewUsers($this->model_dic_id);
        return (new SearchDataScope())->getCondition($query,$rules,$userid)->orWhereRaw("{$userid} in (
            select u.id from tasks as t 
            left join module_users as mu on mu.moduleable_id = t.id and 
            mu.moduleable_type='".ModuleableType::TASK.
            "' left join users as u on u.id = mu.user_id where t.id = tasks.id
        )");
    }
    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
    public function scopeStopAsc($query)
    {
        $now = Carbon::now()->toDateTimeString();
        return $query->orderBy('stop_at')->where('stop_at',$now);
    }

    public function pTask()
    {
        return $this->belongsTo(Task::class, 'task_pid', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'task_pid', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }

    public function resource()
    {
        return $this->hasOne(TaskResource::class);
    }

    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function participants()
    {
        return $this->morphToMany(User::class, 'moduleable', 'module_users')->where('module_users.type', ModuleUserType::PARTICIPANT);
    }

    public function type()
    {
        return $this->belongsTo(TaskType::class);
    }

}
