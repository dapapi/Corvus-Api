<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\OperateLogFollowUpRequest;
use App\Http\Transformers\OperateLogTransformer;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Task;
use App\OperateLogMethod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OperateLogController extends Controller
{

    public function index(Request $request, Task $task, Project $project)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', 1);

        if ($task->id) {
            $query = $task->operateLogs();
        } else if ($project->id) {
            $query = $project->operateLogs();
        }
        //TODO 其他模块

        switch ($status) {
            case 2://不包含跟进
                $query->where('method', '!=', OperateLogMethod::FOLLOW_UP);
                break;
            case 3://只有跟进
                $query->where('method', '=', OperateLogMethod::FOLLOW_UP);
                break;
            case 1://全部
            default:
                break;
        }
        $operateLogs = $query->createDesc()->paginate($pageSize);

        foreach ($operateLogs as $operateLog) {
            if ($operateLog->method == OperateLogMethod::UPDATE_PRIVACY) {
                $operateLog->content = '!!!!!!!';
                //TODO 隐私字段裁切处理
            }
        }

        return $this->response->paginator($operateLogs, new OperateLogTransformer());
    }

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
