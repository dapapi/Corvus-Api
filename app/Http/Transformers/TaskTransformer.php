<?php

namespace App\Http\Transformers;

use App\Models\Task;
use App\TaskStatus;
use App\Traits\OperateLogTrait;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;



class TaskTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['creator', 'pTask', 'tasks', 'resource', 'affixes', 'participants', 'type','operateLogs',  'relate_tasks', 'relate_projects'];

    protected $defaultIncludes = ['principal','type','resource'];

    public function transform(Task $task)
    {
        $array = [
            'id' => hashid_encode($task->id),
            'title' => $task->title,
            'status' => $task->status,
            'priority' => $task->priority,
            'desc' => $task->desc,
            'privacy' => boolval($task->privacy),
            'start_at' => $task->start_at,
            'end_at' => $task->end_at,
            'complete_at' => $task->complete_at,
            'stop_at' => $task->stop_at,
            'created_at' => $task->created_at->toDateTimeString(),
            'updated_at' => $task->updated_at->toDateTimeString(),
            'deleted_at' => $task->deleted_at,
            // 日志内容
            'last_updated_user' => $task->last_updated_user,
            'last_updated_at'   =>  $task->last_updated_at,
            'last_follow_up_at' => $task->last_follow_up_at,
            'adj_id' => $task->adj_id,
        ];

        $array['task_p'] = true;
        if ($task->task_pid) {
            $array['task_p'] = false;
        }

        $operate = DB::table('operate_logs as og')//
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'og.user_id');
            })
            ->where('og.logable_id', $task->id)
            ->where('og.logable_type', 'task')
            ->where('og.method', 2)
            ->select('og.created_at','users.name')->orderBy('created_at','desc')->first();

        $array['operate'] = $operate;

        $user = Auth::guard('api')->user();
        $adjId = $task->adj_id;
        if($adjId !=="0"){
            $adjIdArr = explode(",", $adjId);
            if(in_array($user->id,$adjIdArr)){

            }else{
                unset($task->id);
                $array = [];
            }
        }
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
