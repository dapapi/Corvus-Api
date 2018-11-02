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
                if ($task->id) {
                    $array['moduleable_id'] = $task->id;
                    $array['moduleable_type'] = ModuleableType::TASK;
                } else if ($project->id) {
                    $array['moduleable_id'] = $project->id;
                    $array['moduleable_type'] = ModuleableType::PROJECT;
                }
                //TODO 还有其他类型

                $moduleUser = ModuleUser::where('moduleable_type', $array['moduleable_type'])->where('moduleable_id', $task->id)->where('user_id', $participantUser->id)->where('type', $type)->first();
                if (!$moduleUser) {
                    ModuleUser::create($array);
                } else {
                    array_splice($participantIds, $key, 1);
                }
            }
        }
        return $participantIds;
    }

}
