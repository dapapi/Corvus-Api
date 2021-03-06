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

    private static $model_dic_id = DataDictionarie::PROJECT;

    const TYPE_MOVIE = 1; // 影视项目
    const TYPE_VARIETY = 2; // 综艺项目
    const TYPE_ENDORSEMENT = 3; // 商务代言
//    const TYPE_PAPI = 4; // papi项目
    const TYPE_BASE = 5; // 基础项目

    const STATUS_BEEVALUATING = 1; //评估中
    const STATUS_EVALUATINGACCOMPLISH = 2; //评估完成
    const STATUS_CONTRACT = 3; //签约中
    const STATUS_CONTRACTACCOMPLISH = 4; //签约完成
    const STATUS_EXECUTION = 5; //执行中
    const STATUS_EXECUTIONACCOMPLISH = 6; //执行完成
    const STATUS_RETURNEDMONEY = 7; //回款中
    const STATUS_RETURNEDMONEYACCOMPLISH = 8; //回款完成






    const STATUS_NORMAL = 1; // 进行中
    const STATUS_COMPLETE = 2; // 完成
    const STATUS_FROZEN = 3; // 终止
    const STATUS_DEL = 4; // 删除
    const PROJECT_TYPE = 'projects'; // 业务类型



    protected $fillable = [
        'title',
        'project_number',
        'principal_id',
        'principal_name',
        'department_name',
        'creator_id',
        'creator_name',
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
        'delete_at',
        # 冗余字段
        'principal_name',
        'creator_name',
        # 线索相关字段
        'resource_type',
        'resource',
        'fee',
        'cooperation_type',
        'television_type',
        'play_grade',
    ];

    public static function getProjectNumber()
    {
        return date("Ymd", time()) . rand(100000000, 999999999);
    }

    public function scopeSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $rules = (new ScopeRepository())->getDataViewUsers(self::$model_dic_id);
        return (new SearchDataScope())->getCondition($query, $rules, $userid)->orWhereRaw("{$userid} in (
            select mu.user_id from projects as p
            left join module_users as mu on mu.moduleable_id = p.id and
            mu.moduleable_type='" . ModuleableType::PROJECT .

            "' where p.id = projects.id
        )");


//        return (new SearchDataScope())->getCondition($query, $rules, $userid)->leftJoin('module_users as mu',function ($join){
//            $join->on('mu.moduleable_id','projects.id')
//                ->where('mu.moduleable_type',ModuleableType::PROJECT);
//        })->orWhere('mu.user_id',$user->id);
    }

    public static function getConditionSql()
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $rules = (new ScopeRepository())->getDataViewUsers(self::$model_dic_id);
        $where = (new SearchDataScope())->getConditionSql($rules);
        $where .= <<<AAA
        or ({$userid} in (
                select u.id from project_implode as s
                left join module_users as mu on mu.moduleable_id = s.id and 
                mu.moduleable_type='project' 
                left join users as u on u.id = mu.user_id where s.id = project_implode.id
            )
        )
AAA;
        return $where;

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
    public function relateProjectCourse()
    {
      //  return $this->belongsTo(ProjectStatusLogs::class, 'id', 'logable_id');
        return $this->morphMany(ProjectStatusLogs::class, 'logable');
    }
    public function relateProjectBillsResource()
    {

        return $this->morphMany(ProjectBillsResource::class, 'resourceable');
    }

    public function projectBills()
    {
        return $this->hasMany(ProjectBill::class, 'project_kd_name','title');
    }


    public function getProjectType($type)
    {
        if ($type == self::TYPE_MOVIE){
            return "影视项目";
        }else if(self::TYPE_VARIETY == $type){
            return "综艺项目";
        }else if(self::TYPE_ENDORSEMENT == $type){
            return "商务代言";
        }else if(self::TYPE_PAPI == $type){
            return "papi项目";
        }else if(self::TYPE_BASE == $type){
            return "基础项目";
        }
        return null;
    }
    public function getProjectStatus($status){
        if ($status == Project::STATUS_BEEVALUATING){
            return "评估中";
        }else if ($status == Project::STATUS_EVALUATINGACCOMPLISH){
            return "评估完成";
        }else if ($status == Project::STATUS_CONTRACT){
            return "签约中";
        }else if($status == Project::STATUS_CONTRACTACCOMPLISH){
            return "签约完成";
        }else if($status == Project::STATUS_EXECUTION){
            return "执行中";
        }else if($status == Project::STATUS_EXECUTIONACCOMPLISH){
            return "执行完成";
        }else if($status == Project::STATUS_RETURNEDMONEY){
            return "回款中";
        }else if($status == Project::STATUS_RETURNEDMONEYACCOMPLISH){
            return "回款完成";
        }
    }

    public function exceptions()
    {
        return $this->hasMany(ProjectTalent::class, 'project_id', 'id');
    }

}
