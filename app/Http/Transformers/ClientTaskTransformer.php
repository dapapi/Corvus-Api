<?php

namespace App\Http\Transformers;

use App\Models\Task;
use App\ModuleUserType;

use App\TaskStatus;
use App\Traits\OperateLogTrait;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;



class ClientTaskTransformer extends TransformerAbstract
{
    protected $defaultIncludes = ['type'];

    public function transform(Task $task)
    {
        $array = [
            'id' => hashid_encode($task->id),
            'title' => $task->title,
            'status' => $task->status,
            'end_at' => date('Y-m-d H:i',strtotime($task->end_at)),
        ];
        $userInfo = DB::table('users')//
            ->where('users.id', $task->creator_id)
            ->select('users.name')->first();
        $array['principal']['data']['id'] = hashid_encode($task->principal_id);
        $array['principal']['data']['name'] = $task->principal_name;
        return $array;


    }

    public function includeType(Task $task)
    {
        $type = $task->type;
        if (!$type)
            return null;
        return $this->item($type, new TaskTypeTransformer());
    }

}
