<?php

namespace App\Models;

use App\Helper\Common;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'department_pid',
        'company_id',
        'sort_number',
        'order_by',
        'desc',
        'city',
    ];
    protected $dates = ['delete_at'];
    const DEPARTMENT_HEAD_TYPE = 1; // 部门负责人
    const NOT_DISTRIBUTION_DEPARTMENT= '未分配部门'; // 部门负责人

    const BUSINESS_DEPARTMENT = 207;//商业管理部，锁价时发消息


    public function pDepartment()
    {
        return $this->belongsTo(Department::class, 'department_pid', 'id');
    }

    public function departments()
    {

        return $this->hasMany(Department::class, 'department_pid', 'id')->where('deleted_at', null)->orderBy('sort_number','desc');
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, DepartmentUser::class, 'department_id', 'id', 'id', 'user_id')->where('users.status','!=',3);
    }

    /**
     * 根据父ID查找所有子ID及父ID
     * @param $pid  父ID
     * @return array 返回包含父ID和所有子ID的列表
     */
    public function getSubidByPid($pid){
        //查找所有数据
        $departments = $this->get(['id','department_pid']);

        $list = Common::getTree($departments,$pid,0);
        return $list;
    }
}
