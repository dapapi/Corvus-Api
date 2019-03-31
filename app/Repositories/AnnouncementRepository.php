<?php

namespace App\Repositories;

use App\Models\Announcement;
use App\Models\AnnouncementScope;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\User;

class AnnouncementRepository
{
    /**
     * 获取所有可以看见公告的人的列表
     * @param Announcement $announcement
     * @return mixed
     * @author lile
     * @date 2019-03-20 16:15
     */
    public function getAllUserThatCanSeeTheAnnouncement(Announcement $announcement)
    {
        //获取可以接受公告的部门
        $departments = AnnouncementScope::where('announcement_id',$announcement->id)->select('department_id')->pluck('department_id')->toArray();
        //获取可以获取公告的部门的子级你部门
        $sub_departments = [];
        $department = new Department();
        foreach ($departments as $department_id){
            $temp = $department->getSubidByPid($department_id);
            foreach ($temp as $t)
            {
                array_push($sub_departments,$t->id);
            }
        }
        $departments = array_merge($departments,$sub_departments);
        //获取所有部门的用户
        $users = DepartmentUser::whereIn('department_id',$departments)->pluck('user_id')->toArray();
        return $users;
    }

    public function getPower(User $user,Announcement $announcement)
    {
        $power = [];
        $role_list = $user->roles()->pluck('id')->all();
        $repository = new ScopeRepository();
        $api_list = [
            'edit_announcement' =>  ['uri'  =>  'announcements/{id}','method'   =>  'put']
        ];
        foreach ($api_list as $key => $value){
            try{
                $repository->checkPower($value['method'],$value['method'],$role_list,$announcement);
                $power[$key] = "true";
            }catch (Exception $exception){
                $power[$key] = "false";
            }
        }
    }

}
