<?php

namespace App\Http\Transformers;

use App\Models\DataDictionarie;
use League\Fractal\TransformerAbstract;

class DataDictionarieTransformer extends TransformerAbstract
{

    //protected $availableIncludes = ['creator', 'pTask', 'tasks', 'resource', 'affixes', 'participants', 'type','operateLogs'];

    protected $defaultIncludes = ['dataDictionaries'];

    public function transform(DataDictionarie $dataDictionarie)
    {
        $array = [
            'id' => $dataDictionarie->id,
            'parent_id' => $dataDictionarie->parent_id,
            'code' => $dataDictionarie->code,
            'val' => $dataDictionarie->val,
            'name' => $dataDictionarie->name,
            'description' => $dataDictionarie->description,
            'sort_number' => $dataDictionarie->sort_number,
            'is_selected' => $dataDictionarie->is_selected,

        ];

//        $array['task_p'] = true;
//        if ($task->task_pid) {
//            $array['task_p'] = false;
//        }

        return $array;
    }

    public function includeDataDictionaries(DataDictionarie $dataDictionarie)
    {
        $departments = $dataDictionarie->DataDictionaries;

        return $this->collection($departments, new DataDictionarieTransformer());
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

}
