<?php

namespace App\Models;
use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use App\SignContractStatus;
use App\Traits\PrivacyFieldTrait;
use App\User;
use App\TaskStatus;
use App\Traits\OperateLogTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class Blogger extends Model
{
    use SoftDeletes;
    use PrivacyFieldTrait;
//    use OperateLogTrait;
    protected  $model_dic_id = DataDictionarie::BLOGGER;//模型在数据字典中对应的id
    protected $fillable = [
        'nickname',
        'communication_status',//沟通状态
        'intention',//与我司签约意向
        'intention_desc',//不与我司签约原因
        'sign_contract_at',//签约日期
        'level',//博主级别
        'hatch_star_at',//孵化期开始时间
        'hatch_end_at',//孵化期结束时间
        'producer_id',//制作人
        'sign_contract_status',//签约状态
        'desc',//描述/备注
        'type_id',
        'status',
        'avatar',
        'creator_id',
        'gender',
        'cooperation_demand',//合作需求
        'terminate_agreement_at',//解约日期
        'sign_contract_other',//是否签约其他公司
        'sign_contract_other_name',//签约公司名称
        'platform',//平台

        'douyin_id',//微博url
        'douyin_fans_num',//微博粉丝数
        'weibo_url',//微博url
        'weibo_fans_num',//微博粉丝数
        'xiaohongshu_url',//微博url
        'xiaohongshu_fans_num',//微博粉丝数
        'last_updated_user_id',
        'last_updated_at',
        'last_follow_up_at',
        'last_updated_user',
        'last_follow_up_user_id',
        'last_follow_up_user'

    ];
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $path = Input::path();
        if(starts_with($path,"signing")){//如果接口地址以signing开头则是签约中的艺人
            $this->model_dic_id = DataDictionarie::SIGNING_BLOGGER;
        }
    }

//隐藏字段
//'contract_type',//合同类型
//'divide_into_proportion',//分成比例
    public function scopeSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $rules = (new ScopeRepository())->getDataViewUsers($this->model_dic_id);
        $query->where(function ($query)use ($rules,$userid){
            return (new SearchDataScope())->getCondition($query,$rules,$userid)->orWhereRaw("{$userid} in (
            select u.id from bloggers as b 
            left join module_users as mu on mu.moduleable_id = b.id and 
            mu.moduleable_type='".ModuleableType::BLOGGER.
                "' left join users as u on u.id = mu.user_id where b.id = bloggers.id
        )");
        })->where(function ($query){
            if ($this->model_dic_id == DataDictionarie::SIGNING_BLOGGER){//签约中
                $query->where('sign_contract_status',SignContractStatus::SIGN_CONTRACTING);
            }elseif ($this->model_dic_id == DataDictionarie::BLOGGER){//已签约，已解约
                $query->whereIn('sign_contract_status',[SignContractStatus::ALREADY_SIGN_CONTRACT,SignContractStatus::ALREADY_TERMINATE_AGREEMENT]);
            }
        });

    }

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function tasks()
    {
        //
        return $this->morphToMany(Task::class, 'resourceable', 'task_resources');
    }

    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id', 'id');
    }

    public function type()
    {
        return $this->belongsTo(BloggerType::class, 'type_id', 'id');
    }

    public function trail()
    {
        return $this->morphToMany(Trail::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::EXPECTATION);
    }
    public function publicity()
    {
        return $this->belongsToMany(User::class,"module_users","moduleable_id")->where('type',ModuleUserType::PRODUCER);
    }
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'stars', 'id')->where('star_type','bloggers');
    }
    public function calendars()
    {
        return $this->morphOne(Calendar::class,'starable');
    }
    public function schedules()
    {
        return $this->belongsTo(Schedule::class,'calendar_id','id');
    }
    public function calendar()
    {
        return $this->morphOne(Calendar::class,'starable');
    }
    public function productions()
    {
        return $this->hasMany(Production::class, 'blogger_id', 'id');
    }
    public function bloggerBills()
    {
        return $this->hasMany(ProjectBill::class, 'action_user','nickname');
    }

    /**
     * 博主的参与人定义人制作人，为了权限加的这个方法
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     * @author lile
     * @date 2019-03-28 17:15
     */
    public function participants()
    {
        return $this->morphToMany(User::class, 'moduleable', 'module_users')->wherePivot('type', ModuleUserType::PRODUCER);
    }


    public function projects()
    {
        return $this->morphToMany(Project::class, 'talent', 'project_talent');
    }
}
