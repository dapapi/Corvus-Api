<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/11/26
 * Time: 上午9:43
 */

namespace App\Helper;


use App\Exceptions\ApprovalVerifyException;
use App\Models\Department;
use App\Models\DepartmentPrincipal;
use App\Models\DepartmentUser;

class Common
{
    /**
     * 递归实现无限极分类
     * @param $array 分类数据
     * @param $pid 父ID
     * @param $level 分类级别
     * @return $list 分好类的数组 直接遍历即可 $level可以用来遍历缩进
     */

    public static function getTree($array, $pid =0, $level = 0){

        //声明静态数组,避免递归调用时,多次声明导致数组覆盖
        static $list = [];
        foreach ($array as $key => $value){
            //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
            if ($value['department_pid'] == $pid){
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['level'] = $level;
                //把数组放到list中
                $list[] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($array[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                self::getTree($array, $value['id'], $level+1);

            }
        }
        return $list;
    }

    public static function getChildDepartment($departmentId)
    {
        $arr = [$departmentId];
        foreach (Department::where('department_pid', $departmentId)->cursor() as $department) {
            $childId = self::getChildDepartment($department->id);
            $arr = array_merge($arr, $childId);
        }
        return $arr;
    }

    /**
     * @param $userId
     * @param int $level
     * @return int $departmentPrincipalId
     */
    public static function getDepartmentPrincipal($userId, $level = 0)
    {
        $departmentUser = DepartmentUser::where('user_id', $userId)->first();
        $departmentId = $departmentUser->department_id;

        $departmentPrincipalId = DepartmentPrincipal::where('department_id', $departmentId)->first()->user_id;
        $level = $departmentPrincipalId == $userId ? $level + 1 : $level;

        for ($i = $level;$i > 1; $i--) {
            $departmentNextId = self::getParentDepartment($departmentId);
            if ($departmentNextId != 0) {
                $departmentId = $departmentNextId;
            }
        }
        $departmentPrincipal = DepartmentPrincipal::where('department_id', $departmentId)->first();
        if ($departmentPrincipal) {
            $departmentPrincipalId = $departmentPrincipal->user_id;
        }
        return $departmentPrincipalId;
    }

    public static function getParentDepartment($departmentId)
    {
        $department = Department::where('id', $departmentId)->first();
        if ($department) {
            $departmentPid = $department->department_pid;
        } else {
            $departmentPid = $departmentId;
        }
        return $departmentPid;
    }

    public static function unsetArrayValue($array,$v)
    {
        $res = [];
        foreach ($array as $value){
            if ($v != $value){
                $res[] = $value;
            }
        }
        return $res;
    }
}
