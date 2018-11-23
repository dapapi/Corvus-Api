<?php

namespace App\Repositories;

use App\Models\Affix;
use App\ModuleableType;
use App\User;

class AffixRepository
{
    
    public function addAffix(User $user, $task, $project, $star, $client, $trail, $blogger, $title, $url, $size, $type)
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
        } else if ($client && $client->id) {
            $array['affixable_id'] = $client->id;
            $array['affixable_type'] = ModuleableType::CLIENT;
        } else if ($trail && $trail->id) {
            $array['affixable_id'] = $trail->id;
            $array['affixable_type'] = ModuleableType::TRAIL;
        } else if ($blogger && $blogger->id) {
            $array['affixable_id'] = $blogger->id;
            $array['affixable_type'] = ModuleableType::BLOGGER;
        }
        //TODO 还有其他类型

        $affix = Affix::create($array);
        return $affix;
    }

}
