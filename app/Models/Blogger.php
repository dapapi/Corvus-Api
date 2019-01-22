<?php

namespace App\Models;
use App\ModuleableType;
use App\ModuleUserType;
use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use App\User;
use App\TaskStatus;
use App\Traits\OperateLogTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Blogger extends Model
{
    use SoftDeletes;
    use OperateLogTrait;
    private $model_dic_id = DataDictionarie::BLOGGER;//模型在数据字典中对应的id
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

    ];
//隐藏字段
//'contract_type',//合同类型
//'divide_into_proportion',//分成比例
    public function scopeSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $rules = (new ScopeRepository())->getDataViewUsers($this->model_dic_id);
        return (new SearchDataScope())->getCondition($query,$rules,$userid)->orWhereRaw("{$userid} in (
            select u.id from bloggers as b 
            left join module_users as mu on mu.moduleable_id = b.id and 
            mu.moduleable_type='".ModuleableType::BLOGGER.
            "' left join users as u on u.id = mu.user_id where b.id = bloggers.id
        )");
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
        //->where('status',TaskStatus::NORMAL)          解决已完成任务消失问题
        return $this->morphToMany(Task::class, 'resourceable', 'task_resources')->orderBy('created_at','desc')->limit(3);
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
    public function calendars()
    {
        return $this->morphOne(Calendar::class,'starable');
    }
    public function schedules()
    {
        return $this->belongsTo(Schedule::class,'calendar_id','id');
    }
}
