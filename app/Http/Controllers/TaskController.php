<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskParticipantRequest;
use App\Http\Requests\TaskRequest;
use App\Http\Requests\TaskResourceRequest;
use App\Http\Requests\TaskStatusRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Transformers\TaskTransformer;
use App\Models\ModuleUser;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskResource;
use App\ModuleableType;
use App\ResourceType;
use App\TaskStatus;
use App\User;
use Exception;
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

        //TODO
        dd(Task::where('id', 1)->first()->affixes);
    }

    public function show(Task $task)
    {
        return $this->response()->item($task, new TaskTransformer());
    }

    public function update(TaskUpdateRequest $request, Task $task)
    {
        $payload = $request->all();
        //TODO
    }

    /**
     * 移除参与人
     * @param TaskParticipantRequest $request
     * @param Task $task
     */
    public function removeParticipant(TaskParticipantRequest $request, Task $task)
    {
        $payload = $request->all();
        $participantIds = $payload['participant_ids'];
        $participantIds = array_unique($participantIds);
        DB::beginTransaction();
        try {
            foreach ($participantIds as $key => &$participantId) {
                try {
                    $participantId = hashid_decode($participantId);
                    $participantUser = User::findOrFail($participantId);
                    $moduleUser = ModuleUser::where('moduleable_type', ModuleableType::TASK)->where('moduleable_id', $task->id)->where('user_id', $participantUser->id)->first();
                    if ($moduleUser) {
                        $moduleUser->delete();
                        //TODO 操作日志
                    }
                } catch (Exception $e) {
                    array_splice($participantIds, $key, 1);
                }
            }
            unset($participantId);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
        DB::commit();
        return $this->response->accepted();
    }

    /**
     * 添加参与人
     * @param TaskParticipantRequest $request
     * @param Task $task
     */
    public function addParticipant(TaskParticipantRequest $request, Task $task)
    {
        $payload = $request->all();
        $participantIds = $payload['participant_ids'];
        $participantIds = array_unique($participantIds);

        DB::beginTransaction();
        try {
            foreach ($participantIds as $key => &$participantId) {
                try {
                    $participantId = hashid_decode($participantId);
                    $participantUser = User::findOrFail($participantId);
                    $moduleUser = ModuleUser::where('moduleable_type', ModuleableType::TASK)->where('moduleable_id', $task->id)->where('user_id', $participantUser->id)->first();
                    if (!$moduleUser) {
                        ModuleUser::create([
                            'user_id' => $participantUser->id,
                            'moduleable_id' => $task->id,
                            'moduleable_type' => ModuleableType::TASK,
                            //'type' => 1,
                        ]);
                        //TODO 操作日志
                    }
                } catch (Exception $e) {
                    array_splice($participantIds, $key, 1);
                }
            }
            unset($participantId);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
        DB::commit();
        return $this->response->created();
    }

    public function toggleStatus(TaskStatusRequest $request, Task $task)
    {
        $payload = $request->all();
        $status = $payload['status'];

        if ($task->status == $status)
            return $this->response->noContent();
        switch ($task->status) {
            case TaskStatus::NORMAL:
                break;
            case TaskStatus::COMPLETE:
                if ($status == TaskStatus::TERMINATION)
                    return $this->response->errorBadRequest('完成不能转到终止');
                break;
            case TaskStatus::TERMINATION:
                if ($status == TaskStatus::COMPLETE)
                    return $this->response->errorBadRequest('终止不能转到完成');
                break;
        }

        try {
            $task->status = $status;
            $task->save();
            //TODO 操作日志
        } catch (Exception $e) {
            return $this->response->errorInternal('操作失败');
        }
        return $this->response->accepted();
    }

    public function recoverDestroy(Task $task)
    {
        DB::beginTransaction();
        try {
            $deletedAt = $task->deleted_at;
            if (!$task->task_pid) {
                //删除所有子任务
                $subtasks = Task::where('task_pid', $task->id)->onlyTrashed()->where('deleted_at', $deletedAt)->get();
                foreach ($subtasks as $subtask) {
                    $subtask->restore();
                }
            }
            $task->restore();
            //TODO 操作日志
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('恢复删除失败');
        }
        DB::commit();
        return $this->response->noContent();
    }

    public function destroy(Task $task)
    {

        DB::beginTransaction();
        try {
            if (!$task->task_pid) {
                //删除所有子任务
                $subtasks = Task::where('task_pid', $task->id)->get();
                foreach ($subtasks as $subtask) {
                    $subtask->delete();
                }
            }
            $task->delete();
            //TODO 操作日志
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();
        return $this->response->noContent();
    }

    public function togglePrivacy(Task $task)
    {
        DB::beginTransaction();
        try {
            $task->privacy = !$task->privacy;
            $task->save();
            //TODO 操作日志
            if ($task->privacy)
                return $this->response->accepted();
            return $this->response->noContent();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('操作失败');
        }
        DB::commit();
    }

    /**
     * 解除关联资源
     * @param Request $request
     * @param Project $project
     * @param Task $task
     */
    public function relieveResource(Request $request, Project $project, Task $task)
    {
        $payload = $request->all();
        try {
            $type = 0;
            if ($project->id) {
                $type = ResourceType::PROJECT;
                $project = Project::findOrFail($project->id);
                $resourceable_id = $project->id;
                $resourceable_type = ModuleableType::PROJECT;
            } else {
                //TODO 处理其他资源
            }
            $resource = Resource::where('type', $type)->first();

            $taskResource = TaskResource::where('task_id', $task->id)
                ->where('resourceable_id', $resourceable_id)
                ->where('resourceable_type', $resourceable_type)
                ->where('resource_id', $resource->id)
                ->first();
            if ($taskResource) {
                $taskResource->delete();
                //TODO 操作日志
            } else {
                return $this->response->noContent();
            }
        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('解除关联任务失败');
        }
        return $this->response->accepted();
    }

    /**
     * 关联资源
     * @param Request $request
     * @param Project $project
     * @param Task $task
     */
    public function relevanceResource(Request $request, Project $project, Task $task)
    {
        $payload = $request->all();
        if (!$task->task_pid) {//子任务不能关联资源
            try {
                $array = [
                    'task_id' => $task->id,
                ];

                $type = 0;
                if ($project->id) {
                    $type = ResourceType::PROJECT;
                    $project = Project::findOrFail($project->id);
                    $array['resourceable_id'] = $project->id;
                    $array['resourceable_type'] = ModuleableType::PROJECT;
                } else {
                    //TODO 处理其他资源
                }
                $resource = Resource::where('type', $type)->first();
                $array['resource_id'] = $resource->id;

                $taskResource = TaskResource::where('task_id', $task->id)
                    ->where('resourceable_id', $array['resourceable_id'])
                    ->where('resourceable_type', $array['resourceable_type'])
                    ->where('resource_id', $resource->id)
                    ->first();
                if (!$taskResource) {
                    TaskResource::create($array);
                    //TODO 操作日志
                } else {
                    return $this->response->noContent();
                }
            } catch (Exception $e) {
                Log::error($e);
                return $this->response->errorInternal('关联任务失败');
            }
        }
        return $this->response->created();
    }

    public function store(TaskRequest $request, Task $pTask)
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

        if ($pTask->id) {
            if ($pTask->task_pid)
                return $this->response->errorBadRequest('子任务不支持多级子任务');
            $payload['task_pid'] = $pTask->id;
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
            //TODO 操作日志
            if (!$pTask->id) {//子任务不能关联资源
                //关联资源
                if ($request->has('resource_id') && $request->has('resourceable_id')) {
                    $resourceId = hashid_decode($payload['resource_id']);
                    $resourceableId = hashid_decode($payload['resourceable_id']);
                    $resource = Resource::findOrFail($resourceId);
                    $array = [
                        'task_id' => $task->id,
                        'resource_id' => $resourceId,
                    ];
                    switch ($resource->type) {
                        case ResourceType::BLOGGER:
                            //TODO
                            break;
                        case ResourceType::ARTIST:
                            //TODO
                            break;
                        case ResourceType::PROJECT:
                            $project = Project::findOrFail($resourceableId);
                            $array['resourceable_id'] = $project->id;
                            $array['resourceable_type'] = ModuleableType::PROJECT;
                            break;
                        default:
                            throw new ModelNotFoundException();
                    }

                    TaskResource::create($array);
                    //TODO 操作日志
                }
            }

            //添加参与人
            if ($request->has('participant_ids')) {
                $participantIds = $payload['participant_ids'];
                $participantIds = array_unique($participantIds);
                foreach ($participantIds as $key => &$participantId) {
                    try {
                        $participantId = hashid_decode($participantId);
                        $participantUser = User::findOrFail($participantId);
                        $moduleUser = ModuleUser::where('moduleable_type', ModuleableType::TASK)->where('moduleable_id', $task->id)->where('user_id', $participantUser->id)->first();
                        if (!$moduleUser) {
                            ModuleUser::create([
                                'user_id' => $participantUser->id,
                                'moduleable_id' => $task->id,
                                'moduleable_type' => ModuleableType::TASK,
//                            'type' => 1,
                            ]);
                        }
                        //TODO 操作日志
                    } catch (Exception $e) {
                        array_splice($participantIds, $key, 1);
                    }
                }
                unset($participantId);
            }
        } catch (ModelNotFoundException $e) {
            return $this->response->errorBadRequest();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->created();
    }
}
