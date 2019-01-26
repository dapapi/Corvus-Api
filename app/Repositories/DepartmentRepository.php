<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class DepartmentRepository
{
    public function getUsersByDepartmentId($pid)
    {
        $arr = [];
        $department = DB::table('departments')->where(['department_pid'=>$pid])->get(['id']);
        $user = DB::table('department_user')->where(['department_id'=>$pid])->get(['user_id','department_id']);
        if ($user) {
            foreach ($user as $u) {
                $arr[] = $u->user_id;
            }
        }
        if ($department) {
            foreach ($department as $value) {
                $tmparr = $this->getUsersByDepartmentId($value->id);
                if ($tmparr) {
                    foreach ($tmparr as $key=>$v) {
                        $arr[] = $v;
                    }
                }
            }
        }
        return $arr;
    }
}
