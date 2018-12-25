<?php

namespace App\Models;

use App\ModuleUserType;
use App\Helper\Common;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

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
}
