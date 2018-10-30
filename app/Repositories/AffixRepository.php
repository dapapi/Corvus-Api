<?php

namespace App\Repositories;

use App\Models\Affix;
use App\Models\Project;
use App\Models\Task;
use App\ModuleableType;

class AffixRepository
{
    public function addAffix(Task $task, Project $project, $title, $url, $type)
    {
        $array = [
            'title' => $title,
            'url' => $url,
            'type' => $type,
        ];
        if ($task) {
            $array['affixable_id'] = $task->id;
            $array['affixable_type'] = ModuleableType::TASK;
        } else if ($project) {
            $array['affixable_id'] = $project->id;
            $array['affixable_type'] = ModuleableType::PROJECT;
        }
        //TODO 还有其他类型

        $affix = Affix::create($array);
        return $affix;
    }

}
