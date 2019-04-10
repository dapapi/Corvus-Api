<?php

namespace App\Http\Transformers;

use App\Models\Task;
use App\ModuleUserType;
use App\TaskStatus;
use App\Traits\OperateLogTrait;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;



class TaskUserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['creator', 'pTask', 'tasks', 'resource', 'affixes', 'participants', 'type','operateLogs',  'relate_tasks', 'relate_projects'];


    public function transform(Task $task)
    {
       
        $array = [
            'id' => hashid_encode($task->id),
            'title' => $task->title,

            'end_at' => date('Y-m-d H:i',strtotime($task->end_at)),
            'complete_at' => $task->complete_at,
            'stop_at' => $task->stop_at,

        ];


        return $array;


    }

    public function includeParticipants(Task $task)
    {
        $participants = $task->participants;
        return $this->collection($participants, new UserTransformer());
    }

    public function includeCreator(Task $task)
    {
        $user = $task->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includePrincipal(Task $task)
    {
        $user = $task->principal;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includePTask(Task $task)
    {
        $task = $task->pTask;
        if (!$task)
            return null;
        return $this->item($task, new TaskTransformer());
    }

    public function includeTasks(Task $task)
    {
        $tasks = $task->tasks()->createDesc()->get();
        return $this->collection($tasks, new TaskTransformer());
    }

    public function includeAffixes(Task $task)
    {
        $affixes = $task->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }

    public function includeResource(Task $task)
    {
        $resource = $task->resource;

        if (!$resource)
            return null;
        return $this->item($resource, new TaskResourceTransformer());
    }

    public function includeType(Task $task)
    {
        $type = $task->type;
        if (!$type)
            return null;
        return $this->item($type, new TaskTypeTransformer());
    }

    public function includeOperateLogs(Task $task)
    {
        $type = $task->operateLogs;
       // dd($type);
        if (!$type)
            return null;
        return $this->item($type, new OperateLogTransformer());
    }

    public function includeRelateTasks(Task $task)
    {
        $tasks = $task->relateTasks;
        return $this->collection($tasks, new TaskTransformer());
    }

    public function includeRelateProjects(Task $task)
    {
        $projects = $task->relateProjects;
        return $this->collection($projects, new ProjectTransformer());
    }

}
