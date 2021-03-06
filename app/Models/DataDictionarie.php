<?php
namespace App\Models;
use App\ModuleUserType;
use App\Helper\Common;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
class DataDictionarie extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'parent_id',
        'code',
        'val',
        'name',
        'description',
    ];
    const FORM_STATE_DSP = 231; // 待审批
    const FORM_STATE_YTY = 232; // 已同意
    const FORM_STATE_YJJ = 233; // 已拒绝
    const FORM_STATE_YCX = 234; // 已撤销
    const FORM_STATE_YZF = 235; // 已作废
    //审批表单状态
    const FIOW_TYPE_TJSP = 237; // 提交审批
    const FIOW_TYPE_DSP = 238;  //  待审批
    //知会人类型
    const NOTICE_TYPE_TEAN = 245;  //  团队
    //模块
    const BLOGGER = 3;//博主
    const PROJECT = 4;//项目
    const STAR = 5;//艺人
    const CLIENT = 6;//客户
    const TRAIL = 7;//销售线索
    const TASK = 8;//任务
    const CONTRACTS = 9;//合同
    const REPORTFROM = 10;//报表
    const CALENDAR = 11;//日历
    const ATTENDANCE = 12;//考勤
    const APPROVAL = 13;//审批
    const MESSAGE = 14;//消息
    const PROJECT_BILL = 543;//项目账单
    const BLOGGER_BILL = 544; //博主账单
    const STAR_BILL = 545; //艺人账单
    const SIGNING_STAR = 620;//签约中的艺人
    const SIGNING_BLOGGER = 652;//签约中的博主
    //。。。。
    //销售线索来源类型
    const RESOURCE_TYPE = 37;
    //优先级
    const PRIORITY = 49;
    //合作类型
    const COOPERATION_TYPE = 28;
    //隐私字段
    const PRIVACY_FIELD = 520;//所有隐私字段父id
    public function dataDictionaries()
    {
        return $this->hasMany(DataDictionarie::class, 'parent_id', 'id');
    }
    public function users()
    {
        return $this->hasManyThrough(User::class, DepartmentUser::class, 'department_id', 'id', 'id', 'user_id');
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_resources', 'resouce_id');
    }
    /**
     * 根据父ID查找所有子ID及父ID
     * @param $pid  父ID
     * @return array 返回包含父ID和所有子ID的列表
     */
    public function getSubidByPid($pid){
        //查找所有数据
        $dataDictionaries = $this->get(['id','parent_id']);
        $list = Common::getTree($dataDictionaries,$pid,0);
        return $list;
    }
    // 访问器
    public function getIsSelectedAttribute()
    {
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        $roleId = RoleUser::where('user_id', $userId)->first()->role_id;
        $depatments = DataDictionarie::where('parent_id', 1)->get();
        $role = $this->roles()->where('id', $roleId)->first();
        if ($role) {
            return true;
        } else {
            return false;
        }
    }
    //根据parent_id 和 val获取name
    public function getName($parent_id,$val){
        $res = $this->where('parent_id',$parent_id)->where('val',$val)->first();
        if($res == null){
            return null;
        }
        return $res->name;
    }
    //获取所有隐私字段
    public static function getPrivacyFieldList()
    {
        $privacy_field = Cache::get("privacy_field");
        if ($privacy_field){
            return $privacy_field;
        }
        $privacy_field = self::where('parent_id',self::PRIVACY_FIELD)->pluck('val')->toArray();
        $now = Carbon::now();
        Cache::put("privacy_field",$privacy_field,$now->addMinute(1)); //缓存一分后失效
        return $privacy_field;
    }
    /**
     * 获取某个表的所有隐私字段
     * @author lile
     * @date 2019-03-14 17:38
     */
    public function getPrivacyFieldByTable($table)
    {
        self::where('parent_id',self::PRIVACY_FIELD)->where('')->pluck('val');
    }
}