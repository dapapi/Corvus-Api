<?php

namespace App\Http\Transformers;

use App\Models\Task;
use App\ModuleUserType;

use App\TaskStatus;
use App\Traits\OperateLogTrait;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;



class TaskTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['creator', 'pTask', 'tasks', 'resource', 'affixes', 'participants', 'type','operateLogs',  'relate_tasks', 'relate_projects'];

    protected $defaultIncludes = ['type','resource','affixes'];
    //protected $defaultIncludes = ['affixes','tasks'];
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
            'end_at' => date('Y-m-d H:i',strtotime($task->end_at)),
            'complete_at' => $task->complete_at,
            'stop_at' => $task->stop_at,
            'created_at' => $task->created_at->toDatetimeString(),
            'updated_at' => $task->updated_at->toDatetimeString(),
            'deleted_at' => $task->deleted_at,
            // 日志内容
            'last_updated_user' => $task->last_updated_user,
            'last_updated_at'   =>  $task->last_updated_at,
            'last_follow_up_at' => $task->last_follow_up_at,
            "power" =>  $task->power,
            "powers" => $task->powers,
            'adj_id' => $task->adj_id,
        ];

        $array['task_p'] = true;
        if ($task->task_pid) {
            $array['task_p'] = false;
        }
        if($task->task_pid != null){
            $array['task_pid'] = hashid_encode($task->task_pid);
        }

        $operate = DB::table('operate_logs as og')//
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'og.user_id');
            })
            ->where('og.logable_id', $task->id)
            ->where('og.logable_type', 'task')
            ->where('og.method', 2)
            ->select('og.created_at','users.name')->orderBy('created_at','desc')->first();

        $userInfo = DB::table('users')//
            ->where('users.id', $task->creator_id)
            ->select('users.name','users.icon_url')->first();

        $principalUserInfo = DB::table('users')//
        ->where('users.id', $task->principal_id)
            ->select('users.name','users.icon_url')->first();

        $array['principal']['data']['id'] = hashid_encode($task->principal_id);
        $array['principal']['data']['name'] = $task->principal_name;
        $array['principal']['data']['icon_url'] = $principalUserInfo->icon_url;

        $array['creator']['data']['id'] = hashid_encode($task->creator_id);
        $array['creator']['data']['name'] = $userInfo->name;
        $array['creator']['data']['icon_url'] = $userInfo->icon_url;

        $array['operate'] = $operate;

        //参与人
        $participants = DB::table('module_users as mu')//
        ->join('users', function ($join) {
            $join->on('users.id', '=', 'mu.user_id');
        })
            ->where('mu.moduleable_id', $task->id)
            ->where('mu.type', ModuleUserType::PARTICIPANT)
            ->where('mu.moduleable_type', 'task')

            ->select('users.id','users.name','users.icon_url')->get();
        foreach ($participants as &$value){
            $value->id = hashid_encode($value->id);
        }
        $array['participants']['data'] = $participants;

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
