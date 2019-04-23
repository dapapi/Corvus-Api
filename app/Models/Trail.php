<?php

namespace App\Models;

use App\ModuleableType;
use App\OperateLogMethod;
use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use App\Traits\OperateLogTrait;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Trail extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    use OperateLogTrait;
    private $model_dic_id = DataDictionarie::TRAIL; //数据字典中模块id

    // 线索来源类型
    const PERSONAL = 1;
    const MAIL = 2;
    const SENIOR = 3;

    // 销售进展 用progress_status字段
    const STATUS_UNCONFIRMED = 1;
    const STATUS_CONFIRMED = 2;
    const STATUS_DELETE = 3;
    const STATUS_REFUSE = 0;

    // 线索状态 status字段
    const STATUS_BEGIN = 1;          //开始接洽
    const STATUS_INITIATIVE = 2;     //主动拒绝
    const STATUS_CLIENT = 3;         //客户拒绝
    const STATUS_ENTER = 4;          //进入谈判
    const STATUS_PURPOSE = 5;        //意向合作
    const STATUS_ACCOMPLISH = 6;     //完成立项


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
    const PROGRESS_PROJECT_COMPLETE = 13;
    const PROGRESS_ARCHIVE = 14;

    //
    const TYPE_MOVIE = 1; // 影视项目
    const TYPE_VARIETY = 2; // 综艺项目
    const TYPE_ENDORSEMENT = 3; // 商务代言
    const TYPE_PAPI = 4; // papi项目
    const TYPE_BASE = 5; // 基础项目

    const COMMERCIAL_PERSONNEL = 123;   //营销商务拓展人员
    const PRODUCTION_PERSONNEL = 117;  //影视资源组
    const VARIETY_PERSONNEL = 138;   // 综艺资源组
    const ADMINISTRATOR = 1;        //管理员

    // priority 优先级
    const PRIORITY_C = 1;
    const PRIORITY_B = 2;
    const PRIORITY_A = 3;
    const PRIORITY_S = 4;

    // 商业管理部
    const  WORLDWIDE = 207;


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
        // 张峪铭 2019-01-24 20:29  增加锁价人和锁价时间两个字段
        'lock_user',
        'lock_at',
        // 张峪铭 2019-01-24 20:29  增加锁价人和锁价时间两个字段
        'progress_status',
        'resource',
        'resource_type',
        'take_type',
        'pool_type',
        'receive',
        'fee',
        'desc',
        'play_grade',             //剧本评分
        'television_type'         //影视类型
    ];

    protected $dates = ['deleted_at'];

    public function scopeSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $department_id =  $this::WORLDWIDE;
            $department_ids = Department::where('department_pid', $department_id)->get(['id']);
            $is_papi = DepartmentUser::whereIn('department_id', $department_ids)->where('user_id',$userid)->get(['user_id'])->toArray();
            if($is_papi){
                $user_list = DepartmentUser::whereIn('department_id', $department_ids)->get(['user_id'])->toArray();
                $user_id = array();
               foreach ($user_list as $val){
                   $user_id[] = $val['user_id'];
               }
                $array['rules'][] =  ['field' => 'trails.creator_id','op' => 'in','value' => $user_id];
                $array['rules'][] =  ['field' => 'trails.principal_id','op' => 'in','value' => $user_id];
                $array['op'] =  'or';
                $rules = $array;
                $extras  = (new SearchDataScope())->getCondition($query,$rules,$userid)->where('lock_status','1');
              //  dd($extras->get()->toArray());
            }else{
                $rules = (new ScopeRepository())->getDataViewUsers($this->model_dic_id);
                return (new SearchDataScope())->getCondition($query,$rules,$userid);
            }
            $rules = (new ScopeRepository())->getDataViewUsers($this->model_dic_id);
            return $this->orCondition($query,$rules);

    }
    public function orCondition($query,$rules)
    {
        if($rules === null){
            return $query->where(DB::raw('0 = 1')); //不查询任何数据
        }
        if(is_array($rules) && count($rules) == 0){
            return $query;
        }
        switch ($rules['op']){
            case 'or':
                $query->orwhere(function ($query)use ($rules){
                    foreach ($rules['rules'] as $key => $value){
                        switch ($value['op']){
                            case 'in':
                                if($value['value'] == null){
                                    $condition[] = $query->orWhere("{$value['field']}");
                                }else{
                                    $condition[] = $query->orWhereIn($value['field'],$value['value']);
                                }
                                break;
                            case '>':
                            case '>=':
                            case '<':
                            case '<=':
                            case 'like':
                                $condition[] = $query->orWhere($value['field'],$value['op'],$value['value']);
                        }
                    }
                });
                break;
            case 'and':
                $query->orwhere(function ($query)use ($rules){
                    foreach ($rules['rules'] as $key => $value){
                        switch ($value['op']){
                            case 'in':
                                if($value['value'] == null){
                                    $condition[] = $query->where(DB::raw("{$value['field']} in (null)"));
                                }else{
                                    $condition[] = $query->whereIn($value['field'],$value['value']);
                                }
                                break;
                            case '>':
                            case '>=':
                            case '<':
                            case '<=':
                            case 'like':
                                $condition[] = $query->Where($value['field'],$value['op'],$value['value']);
                        }
                    }
                });
                break;
            default:
                break;
        }
        return $query;
    }
    public function scopeCompleted($query)
    {
        $query->where('status',Project::STATUS_COMPLETE);
    }
    public function scopePoolType($query,$type=null){
        $query->where("pool_type",$type);
    }

    public function scopeConfirmed($query)
    {
        $ids = DB::table('projects')->leftJoin('approval_flow_execute', function($join) {
            $join->on('projects.project_number', 'approval_flow_execute.form_instance_number');
        })->where('status', '=', Project::STATUS_NORMAL)
            ->where('flow_type_id', '=', 231)
            ->pluck('trail_id')->toArray();
//        $sql_with_bindings = str_replace_array('?', $ids->getBindings(), $ids->toSql());
//        dd($sql_with_bindings);
        $ids = array_unique($ids);

        $query->where('progress_status', self::STATUS_UNCONFIRMED)->whereNotIn('id', $ids);
    }

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }
    public function lockUser()
    {
        return $this->belongsTo(User::class, 'lock_user', 'id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // todo 优化
    public function expectations()
    {
        return $this->morphedByMany(Star::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::EXPECTATION);
    }



    public function recommendations()
    {
        return $this->morphedByMany(Star::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::RECOMMENDATION);
    }



    public function starExpectations()
    {
        return $this->morphedByMany(Star::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::EXPECTATION);
    }
    public function bloggerExpectations()
    {
        return $this->morphedByMany(Blogger::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::EXPECTATION);
    }

    public function starRecommendations()
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

    public function getPriority($priority)
    {
        $res = null;
        switch ($priority){
            case 1:
                $res = "C";
                break;
            case 2:
                $res = "B";
                break;
            case 3:
                $res = "A";
                break;
            case 4:
                $res = "S";
                break;
        }
        return $res;
    }
}
