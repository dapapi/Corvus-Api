<?php

namespace App\Http\Transformers;

use App\Models\Task;
use League\Fractal\TransformerAbstract;

class TaskTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['creator', 'principal', 'pTask'];

    public function transform(Task $task)
    {
        return [
            'id' => hashid_encode($task->id),
            'title' => $task->title,
            'type' => $task->type,
            'status' => $task->status,
            'priority' => $task->priority,
            'desc' => $task->desc,
            'start_at' => $task->start_at ? $task->start_at->toDatetimeString() : null,
            'end_at' => $task->end_at ? $task->end_at->toDatetimeString() : null,
            'complete_at' => $task->complete_at ? $task->complete_at->toDatetimeString() : null,
            'stop_at' => $task->stop_at ? $task->stop_at->toDatetimeString() : null,
            'created_at' => $task->created_at->toDatetimeString(),
            'updated_at' => $task->updated_at->toDatetimeString(),
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
    
}