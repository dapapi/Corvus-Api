<?php

namespace App\Http\Transformers;

use App\Models\Task;
use League\Fractal\TransformerAbstract;

class TaskTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['creator', 'principal', 'pTask', 'tasks', 'resource', 'affixes'];

    public function transform(Task $task)
    {
        return [
            'id' => hashid_encode($task->id),
            'title' => $task->title,
            'type' => $task->type,
            'status' => $task->status,
            'priority' => $task->priority,
            'desc' => $task->desc,
            'privacy' => boolval($task->privacy),
            'start_at' => $task->start_at,
            'end_at' => $task->end_at,
            'complete_at' => $task->complete_at,
            'stop_at' => $task->stop_at,
            'created_at' => $task->created_at->toDatetimeString(),
            'updated_at' => $task->updated_at->toDatetimeString(),
            'deleted_at' => $task->deleted_at,
        ];
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

}
