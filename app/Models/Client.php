<?php

namespace App\Models;

use App\OperateLogMethod;
use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use App\Traits\OperateLogTrait;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Client extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    use OperateLogTrait;
    private $model_dic_id = DataDictionarie::CLIENT;//数据字典中模块id
    const TYPE_MOVIE = 1; // 影视项目
    const TYPE_VARIETY = 2; // 综艺项目
    const TYPE_ENDORSEMENT = 3; // 商务代言
    const TYPE_PAPI = 4; // papi项目

    const SIZE_LISTED = 1;
    const SIZE_TOP500 = 2;

    const GRADE_NORMAL = 1;
    const GRADE_PROXY = 2;

    const STATUS_NORMAL = 1;
    const STATUS_FROZEN = 2;

    const PROTECTION_TIME = 90;//直客保护时间

    protected $fillable = [
        'company',
        'grade',             // 级别
        'client_rating',     // 客户评级
        'province',
        'city',
        'district',
        'address',
        'principal_id',
        'creator_id',
        'size',             // 规模
        'desc',
        'type',             // 商务客户
        'status',
    ];

    protected $dates = ['deleted_at'];

    public function scopeSearchData($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $department_id = Department::where('name', '商业管理部')->first();
        if($department_id) {
            $department_ids = Department::where('department_pid', $department_id->id)->get(['id']);
            $is_papi = DepartmentUser::whereIn('department_id', $department_ids)->where('user_id',$userid)->get(['user_id'])->toArray();
            if($is_papi){
                $user_list = DepartmentUser::whereIn('department_id', $department_ids)->get(['user_id'])->toArray();
                $user_id = array();

                foreach ($user_list as $val){
                    $user_id[] = $val['user_id'];
                }
                $array['rules'][] =  ['field' => 'creator_id','op' => 'in','value' => $user_id];
                $array['rules'][] =  ['field' => 'principal_id','op' => 'in','value' => $user_id];
                $array['op'] =  'or';
                $rules = $array;
                $extras =(new SearchDataScope())->getCondition($query,$rules,$userid)->where('grade','1');
                $extra = $extras->get()->toArray();
            }
        }else{
            $rules = (new ScopeRepository())->getDataViewUsers($this->model_dic_id);
            return (new SearchDataScope())->getCondition($query,$rules,$userid);
        }

        $rules = (new ScopeRepository())->getDataViewUsers($this->model_dic_id);
        return (new Trail())->orCondition($query,$rules);
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

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'resourceable','task_resources');
    }

    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }

    public function getKeymanAttribute()
    {
        $contacts = $this->contacts()->where('type', Contact::TYPE_KEY)->pluck('name')->toArray();
        return implode(",", $contacts);
    }
    public function getGrade($grade)
    {
        if ($grade == Client::GRADE_NORMAL) {
            return "直客";
        }
        if ($grade == Client::GRADE_PROXY) {
            return "代理公司";
        }
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'client_id', 'id');
    }
}
