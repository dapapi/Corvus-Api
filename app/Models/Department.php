<?php

namespace App\Models;

use App\Helper\Common;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{

    protected $fillable = [
        'name',
        'department_pid',
        'desc',
        'city',
    ];
    const DEPARTMENT_HEAD_TYPE = 1; // 部门负责人
    const NOT_DISTRIBUTION_DEPARTMENT= '未分配部门'; // 部门负责人


    public function pDepartment()
    {
        return $this->belongsTo(Department::class, 'department_pid', 'id');
    }

    public function departments()
    {

        return $this->hasMany(Department::class, 'department_pid', 'id');
    }

    public function users()
    {

        return $this->hasManyThrough(User::class, DepartmentUser::class, 'department_id', 'id', 'id', 'user_id');
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
