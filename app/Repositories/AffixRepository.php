<?php

namespace App\Repositories;

use App\Models\Affix;
use App\Models\Attendance;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\Report;
use App\Models\Project;
use App\Models\Announcement;
use App\Models\Star;
use App\Models\Task;
use App\Models\Trail;
use App\ModuleableType;
use App\User;

class AffixRepository
{
    
    public function addAffix(User $user, $model, $title, $url, $size, $type)
    {
        $array = [
            'user_id' => $user->id,
            'title' => $title,
            'size' => $size,
            'url' => $url,
            'type' => $type,
        ];
        if ($model instanceof Task && $model->id) {
            $array['affixable_id'] = $model->id;
            $array['affixable_type'] = ModuleableType::TASK;
        } else if ($model instanceof Project && $model->id) {
            $array['affixable_id'] = $model->id;
            $array['affixable_type'] = ModuleableType::PROJECT;
        } else if ($model instanceof Star && $model->id) {
            $array['affixable_id'] = $model->id;
            $array['affixable_type'] = ModuleableType::STAR;
        } else if ($model instanceof Client && $model->id) {
            $array['affixable_id'] = $model->id;
            $array['affixable_type'] = ModuleableType::CLIENT;
        } else if ($model instanceof Report && $model->id) {
            $array['affixable_id'] = $model->id;
            $array['affixable_type'] = ModuleableType::REPORT;
        } else if ($model instanceof Trail && $model->id) {
            $array['affixable_id'] = $model->id;
            $array['affixable_type'] = ModuleableType::TRAIL;
        } else if ($model instanceof Blogger && $model->id) {
            $array['affixable_id'] = $model->id;
            $array['affixable_type'] = ModuleableType::BLOGGER;
        } else if($model instanceof Announcement && $model->id){
            $array['affixable_id'] = $model->id;
            $array['affixable_type'] = ModuleableType::ANNOUNCEMENT;
        } else if($model instanceof Attendance == ModuleableType::ATTENDANCE){
            $array['affixable_id'] = $model->id;
            $array['affixable_type'] = ModuleableType::ATTENDANCE;
        }
        //TODO 还有其他类型
        //
        $affix = Affix::create($array);
        return $affix;
    }

}
