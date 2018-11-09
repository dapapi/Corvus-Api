<?php

namespace App\Repositories;

use App\Models\Affix;
use App\Models\Project;
use App\Models\Star;
use App\Models\Task;
use App\ModuleableType;
use App\User;

class AffixRepository
{
    public function addAffix(User $user, Task $task, Project $project, Star $star, $title, $url, $size, $type)
    {
        $array = [
            'user_id' => $user->id,
            'title' => $title,
            'size' => $size,
            'url' => $url,
            'type' => $type,
        ];
        if ($task && $task->id) {
            $array['affixable_id'] = $task->id;
            $array['affixable_type'] = ModuleableType::TASK;
        } else if ($project && $project->id) {
            $array['affixable_id'] = $project->id;
            $array['affixable_type'] = ModuleableType::PROJECT;
        } else if ($star && $star->id) {
            $array['affixable_id'] = $star->id;
            $array['affixable_type'] = ModuleableType::STAR;
        }
        //TODO 还有其他类型

        $affix = Affix::create($array);
        return $affix;
    }

}
