<?php

namespace App\Repositories;

use App\Models\ModuleUser;
use App\ModuleableType;
use App\ModuleUserType;
use App\User;
use Exception;

class ModuleUserRepository
{

    /**
     * @param $participantIds
     * @param $task
     * @param $project
     * @param $type
     */
    public function addModuleUser($participantIds, $task, $project, $star, $type)
    {
        $participantDeleteIds = [];
        $participantIds = array_unique($participantIds);
        foreach ($participantIds as $key => &$participantId) {
            try {
                $participantId = hashid_decode($participantId);
                $participantUser = User::findOrFail($participantId);
            } catch (Exception $e) {
                array_splice($participantIds, $key, 1);
            }
            if ($participantUser) {
                $array = [
                    'user_id' => $participantUser->id,
                    'type' => $type,
                ];

                if ($task && $task->id) {
                    $array['moduleable_id'] = $task->id;
                    $array['moduleable_type'] = ModuleableType::TASK;
                } else if ($project && $project->id) {
                    $array['moduleable_id'] = $project->id;
                    $array['moduleable_type'] = ModuleableType::PROJECT;
                } else if ($star && $star->id) {
                    $array['moduleable_id'] = $star->id;
                    $array['moduleable_type'] = ModuleableType::STAR;
                } else {
                    throw new Exception('ModuleUserRepository@addModuleUser#1(没有处理这个类型)');
                }
                //TODO 还有其他类型

                $moduleUser = ModuleUser::where('moduleable_type', $array['moduleable_type'])->where('moduleable_id', $array['moduleable_id'])->where('user_id', $participantUser->id)->where('type', $type)->first();
                if (!$moduleUser) {
                    ModuleUser::create($array);
                } else {
                    array_splice($participantIds, $key, 1);
                    $participantDeleteIds[] = $participantId;
                    //前端要求一个接口可以完成添加人和删除人,已经存在的删除
                    $moduleUser->delete();
                }
            }
        }
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
