<?php

namespace App\Repositories;

use App\Models\Affix;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\Project;
use App\Models\Star;
use App\Models\Task;
use App\Models\Trail;
use App\ModuleableType;
use App\User;

class OperateLogRepository
{
    public function getObject(Task $task, Project $project, Star $star, Client $client, Trail $trail, Blogger $blogger)
    {
        $obj = null;
        if ($task && $task->id) {
            $obj = $task;
        } else if ($project && $project->id) {
            $obj = $project;
        } else if ($star && $star->id) {
            $obj = $star;
        } else if ($client && $client->id) {
            $obj = $client;
        } else if ($trail && $trail->id) {
            $obj = $trail;
        } else if ($blogger && $blogger->id) {
            $obj = $blogger;
        }
        //TODO class type
        return $obj;
    }

}
