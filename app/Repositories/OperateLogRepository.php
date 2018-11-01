<?php

namespace App\Repositories;

use App\Models\Affix;
use App\Models\Project;
use App\Models\Task;
use App\ModuleableType;
use App\User;

class OperateLogRepository
{
    public function getObject(Task $task, Project $project)
    {
        $obj = null;
        if ($task->id) {
            $obj = $task;
        } else if ($project->id) {
            $obj = $project;
        }
        //TODO class type
        return $obj;
    }

}
