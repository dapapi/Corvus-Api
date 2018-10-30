<?php

namespace App\Repositories;

use App\Models\ModuleUser;
use App\ModuleableType;
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
    public function addModuleUser($participantIds, $task, $project, $type)
    {
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
                if ($task) {
                    $array['moduleable_id'] = $task->id;
                    $array['moduleable_type'] = ModuleableType::TASK;
                } else if ($project) {
                    $array['moduleable_id'] = $project->id;
                    $array['moduleable_type'] = ModuleableType::PROJECT;
                }
                //TODO 还有其他类型

                $moduleUser = ModuleUser::where('moduleable_type', $array['moduleable_type'])->where('moduleable_id', $task->id)->where('user_id', $participantUser->id)->first();
                if (!$moduleUser) {
                    ModuleUser::create($array);
                    //TODO 操作日志
                }
            }
        }
        unset($participantId);
    }

    public function addTaskModuleUser($participantIds, $task, $type)
    {
        return $this->addModuleUser($participantIds, $task, null, $type);
    }

    public function addProjectModuleUser($participantIds, $project, $type)
    {
        return $this->addModuleUser($participantIds, null, $project, $type);
    }

    /**
     * @param $participantIds
     * @param $task
     * @param $project
     */
    public function delModuleUser($participantIds, $task, $project)
    {
        $participantIds = array_unique($participantIds);
        foreach ($participantIds as $key => &$participantId) {
            try {
                $participantId = hashid_decode($participantId);
                $participantUser = User::findOrFail($participantId);
            } catch (Exception $e) {
                array_splice($participantIds, $key, 1);
            }
            if ($participantUser) {
                $moduleableType = null;
                if ($task) {
                    $moduleableType = ModuleableType::TASK;
                } else if ($project) {
                    $moduleableType = ModuleableType::PROJECT;
                }
                //TODO 还有其他类型

                $moduleUser = ModuleUser::where('moduleable_type', $moduleableType)->where('moduleable_id', $task->id)->where('user_id', $participantUser->id)->first();
                if ($moduleUser) {
                    $moduleUser->delete();
                    //TODO 操作日志
                }
            }
        }
        unset($participantId);
    }

    public function delTaskModuleUser($participantIds, $task)
    {
        return $this->delModuleUser($participantIds, $task, null);
    }

    public function delProjectModuleUser($participantIds, $project)
    {
        return $this->delModuleUser($participantIds, null, $project);
    }
}
