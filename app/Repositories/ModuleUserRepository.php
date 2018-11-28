<?php

namespace App\Repositories;

use App\Models\Calendar;
use App\Models\ModuleUser;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Star;
use App\Models\Task;
use App\ModuleableType;
use App\ModuleUserType;
use App\User;
use Exception;

class ModuleUserRepository
{

    /**
     * @param $participantIds  参与人或宣传人ID数组
     * @param $task
     * @param $project
     * @param $type
     */
    public function addModuleUser($participantIds, $participantDeleteIds, $model, $type)
    {

        $array = [
            'type' => $type,
        ];

        if ($model instanceof Task && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::TASK;
        } else if ($model instanceof Project && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::PROJECT;
        } else if ($model instanceof Star && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::STAR;
        } else if ($model instanceof Calendar && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::CALENDAR;
        } else if ($model instanceof Schedule && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::SCHEDULE;
        } else {
            throw new Exception('ModuleUserRepository@addModuleUser#1(没有处理这个类型)');
        }
        //TODO 还有其他类型

        $participantDeleteIds = array_unique($participantDeleteIds);//去除数组中的重复值
        foreach ($participantDeleteIds as $key => &$participantDeleteId) {
            try {
                $participantDeleteId = hashid_decode($participantDeleteId);//解码
                $participantDeleteUser = User::findOrFail($participantDeleteId);//从用户标中查找
                $array['user_id'] = $participantDeleteUser->id;
                //查找moduleUser表中
                $moduleUser = ModuleUser::where('moduleable_type', $array['moduleable_type'])->where('moduleable_id', $array['moduleable_id'])->where('user_id', $participantDeleteUser->id)->where('type', $type)->first();
                if ($moduleUser) {//数据存在则从数据库中删除
                    $moduleUser->delete();
                } else {//不存在则将ID从要删除的参与人或者宣传人列表中删除
                    array_splice($participantDeleteIds, $key, 1);
                }
            } catch (Exception $e) {
                array_splice($participantDeleteIds, $key, 1);
            }
        }

        $participantIds = array_unique($participantIds);//去除参与人或者宣传人列表的重复值
        foreach ($participantIds as $key => &$participantId) {
            try {
                $participantId = hashid_decode($participantId);
                $participantUser = User::findOrFail($participantId);
                $array['user_id'] = $participantUser->id;

                $moduleUser = ModuleUser::where('moduleable_type', $array['moduleable_type'])->where('moduleable_id', $array['moduleable_id'])->where('user_id', $participantUser->id)->where('type', $type)->first();
                if (!$moduleUser) {//不存在则添加
                    ModuleUser::create($array);
                } else {//存在则从列表中删除
                    array_splice($participantIds, $key, 1);
//                    $participantDeleteIds[] = $participantId;
//                    //要求一个接口可以完成添加人和删除人,已经存在的删除
//                    $moduleUser->delete();
                }
            } catch (Exception $e) {
                array_splice($participantIds, $key, 1);
            }
        }
        //返回添加成功或者删除成功的参与人和宣传人
        return [$participantIds, $participantDeleteIds];
    }

    public function getTypeName($type)
    {
        $title = '参与人';
        switch ($type) {
            case ModuleUserType::PARTICIPANT:
                $title = '参与人';
                break;
            case ModuleUserType::PUBLICITY:
                $title = '宣传人';
                break;
            //TODO
        }
        return $title;
    }

}
