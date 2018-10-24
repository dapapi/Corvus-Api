<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Transformers\TaskTransformer;
use App\Models\ModuleUser;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskResource;
use App\ModuleableType;
use App\ResourceType;
use App\User;
use Exception;
use http\Exception\BadMessageException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = config('app.page_size');
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        }

        dd(Task::where('id', 1)->first()->affixes);
    }


    public function show(Task $task)
    {
        $task = Task::where('id', $task->id)->first();
        return $this->response()->item($task, new TaskTransformer());
    }

    public function store(TaskRequest $request, Task $subtask)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        unset($payload['status']);
        unset($payload['complete_at']);
        unset($payload['stop_at']);

        $payload['creator_id'] = $user->id;

        if ($request->has('principal_id')) {
            try {
                $principalId = hashid_decode($payload['principal_id']);
                User::findOrFail($principalId);
            } catch (Exception $e) {
                return $this->response->errorBadRequest();
            }
        }

        if ($subtask->id) {
            $payload['task_pid'] = $subtask->id;
        }

        if ($request->has('type')) {
            //TODO 验证 type
//                $payload['type']
        }

        if (!$request->has('privacy')) {
            $payload['privacy'] = false;
        }

        DB::beginTransaction();
        try {
            $task = Task::create($payload);
            //关联资源
            if ($request->has('resource_id') && $request->has('resourceable_id')) {
                $resourceId = hashid_decode($payload['resource_id']);
                $resourceableId = hashid_decode($payload['resourceable_id']);
                $resource = Resource::findOrFail($resourceId);
                switch ($resource->type) {
                    case ResourceType::BLOGGER:
                        //TODO
                        break;
                    case ResourceType::ARTIST:
                        //TODO
                        break;
                    case ResourceType::PROJECT:
                        $project = Project::findOrFail($resourceableId);
                        TaskResource::create([
                            'task_id' => $task->id,
                            'resourceable_id' => $project->id,
                            'resourceable_type' => ModuleableType::PROJECT,
                            'resource_id' => $resourceId,
                        ]);
                        break;
                    default:
                        throw new ModelNotFoundException();
                }
            }

            //添加参与人
            if ($request->has('participant_ids')) {
                $participantIds = $payload['participant_ids'];
                $participantIds = array_unique($participantIds);
                foreach ($participantIds as $key => &$participantId) {
                    $participantUser = null;
                    try {
                        $participantId = hashid_decode($participantId);
                        $participantUser = User::findOrFail($participantId);
                    } catch (Exception $e) {
                        array_splice($participantIds, $key, 1);
                    }
                    if ($participantUser) {
                        ModuleUser::create([
                            'user_id' => $participantUser->id,
                            'moduleable_id' => $task->id,
                            'moduleable_type' => ModuleableType::TASK,
//                            'type' => 1,
                        ]);
                    }
                }
                unset($participantId);
            }

            //TODO 操作日志
        } catch (ModelNotFoundException $e) {
            return $this->response->errorBadRequest();
        } catch (Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->created();
    }
}
