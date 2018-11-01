<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\OperateLogFollowUpRequest;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Task;
use App\OperateLogLevel;
use App\OperateLogMethod;
use Exception;
use Illuminate\Support\Facades\Log;

class OperateLogController extends Controller
{

    public function addFollowUp(OperateLogFollowUpRequest $request, Task $task, Project $project)
    {
        $payload = $request->all();
        $content = $payload['content'];

        try {
            $array = [
                'obj' => $task,
                'title' => null,
                'start' => $content,
                'end' => null,
                'method' => OperateLogMethod::FOLLOW_UP,
                'level' => OperateLogLevel::LOW
            ];
            if ($task->id) {
                $array['obj'] = $task;
            } else if ($project->id) {
                $array['obj'] = $project;
            }
            //TODO

            $operate = new OperateEntity($array);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('跟进失败');
        }

        return $this->response->created();
    }
}
