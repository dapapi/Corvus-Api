<?php

namespace App\Models;

use App\ModuleableType;
use App\ModuleUserType;
use App\Repositories\PrivacyUserRepository;
use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use App\TaskStatus;
use App\Traits\OperateLogTrait;
use App\Traits\PrivacyFieldTrait;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Star extends Model
{
    use SoftDeletes;
    use OperateLogTrait;
    use PrivacyFieldTrait;
    private  $model_dic_id = DataDictionarie::STAR;//数据字典中模块id
    protected $fillable = [
        'name',//姓名
        'desc',//描述
        'broker_id',//经纪人ID
        'principal_id',//负责人
        'avatar',//头像
        'gender',//性别
        'birthday',//生日
        'phone',//电话
        'wechat',//微信
        'email',//邮箱
        'source',//艺人来源
        'communication_status',//沟通状态
        'intention',//与我司签约意向
        'intention_desc',//不与我司签约原因
        'sign_contract_other',//是否签约其他公司
        'sign_contract_other_name',//签约公司名称
        'sign_contract_at',//签约日期
        'sign_contract_status',//签约状态  签约中，已签约，已解约
        'terminate_agreement_at',//解约日期
        'creator_id',//录入人
        'status',//项目状态  进行中  已完成  撤单
        'type',//合同类型 全约  其他

        'platform',//社交平台
        'weibo_url',//微博主页地址
        'weibo_fans_num',//微博粉丝数
        'baike_url',//百科地址
        'baike_fans_num',//百科粉丝数
        'douyin_id',//抖音ID
        'douyin_fans_num',//抖音粉丝数
        'qita_url',//其他平台地址
        'qita_fans_num',//其他平台粉丝数
        'artist_scout_name',//星探
        'star_location',//地区
        'star_risk_point',//艺人风险点
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
            select u.id from stars as s
            left join module_users as mu on mu.moduleable_id = s.id and 
            mu.moduleable_type='".ModuleableType::STAR.
            "' left join users as u on u.id = mu.user_id where s.id = stars.id
        )");
    }



    //按创建时间倒叙
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
        return $this->morphToMany(Task::class, 'resourceable','task_resources');
    }
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'stars', 'id')->where('star_type','stars');
    }

    public function broker()
    {
        return $this->belongsToMany(User::class,'module_users','moduleable_id')->where('type',ModuleUserType::BROKER);
    }

    public function starReports()
    {
        return $this->morphMany(StarReport::class,'starable');
    }

    public function starPlatform()
    {
        return $this->hasMany(StarPlatform::class);
    }

    public function trails()
    {
        return $this->morphToMany(Trail::class, 'starable', 'trail_star')->wherePivot('type', TrailStar::EXPECTATION);
    }
    public function works(){
        return $this->hasMany(Work::class);
    }
    public function publicity()
    {
        return $this->belongsToMany(User::class,"module_users","moduleable_id")->where('type',ModuleUserType::PUBLICITY);
    }
    public function calendar()
    {
        return $this->morphOne(Calendar::class,'starable');
    }
    public function schedules()
    {
        return $this->belongsTo(Schedule::class,'calendar_id','id');
    }
    public function projects()
    {

    }

    /**
     * 艺人的参与人定义人经理，为了权限加的这个方法
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     * @author lile
     * @date 2019-03-28 17:15
     */
    public function participants()
    {
        return $this->morphToMany(User::class, 'moduleable', 'module_users')->wherePivot('type', ModuleUserType::BROKER);
    }

}
