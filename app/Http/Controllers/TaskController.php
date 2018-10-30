<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskCancelTimeRequest;
use App\Http\Requests\TaskParticipantRequest;
use App\Http\Requests\TaskRequest;
use App\Http\Requests\TaskStatusRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Transformers\TaskTransformer;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskResource;
use App\ModuleableType;
use App\ModuleUserType;
use App\Repositories\ModuleUserRepository;
use App\ResourceType;
use App\TaskStatus;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class TaskController extends Controller
{
    protected $moduleUserRepository;

    public function __construct(ModuleUserRepository $moduleUserRepository)
    {
        $this->moduleUserRepository = $moduleUserRepository;
    }

    public function index(Request $request)
    {
        $payload = $request->all();
        $pageSize = config('app.page_size');
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        }

        $tasks = Task::createDesc()->paginate($pageSize);

        return $this->response->paginator($tasks, new TaskTransformer());
    }

    public function my(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = config('app.page_size');
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        }

        $tasks = DB::table('tasks')->select('tasks.*')->where('creator_id', $user->id)->orWhere('principal_id', $user->id);
        $query = DB::table('tasks')->select('tasks.*')->join('module_users', function ($join) use ($user) {
            $join->on('module_users.moduleable_id', '=', 'tasks.id')
                ->where('module_users.moduleable_type', ModuleableType::TASK)
                ->where('module_users.user_id', $user->id);
        })
            ->union($tasks);

        $querySql = $query->toSql();
        $result = Task::rightJoin(DB::raw("($querySql) as a"), function ($join) {
            $join->on('tasks.id', '=', 'a.id');
        })
            ->mergeBindings($query)
            ->orderBy('a.created_at', 'desc')
            ->paginate($pageSize);

        return $this->response->paginator($result, new TaskTransformer());
    }

    public function recycleBin(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = config('app.page_size');
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        }

        $tasks = DB::table('tasks')->select('tasks.*')->where('creator_id', $user->id)->orWhere('principal_id', $user->id);
        $query = DB::table('tasks')->select('tasks.*')->join('module_users', function ($join) use ($user) {
            $join->on('module_users.moduleable_id', '=', 'tasks.id')
                ->where('module_users.moduleable_type', ModuleableType::TASK)
                ->where('module_users.user_id', $user->id);
        })
            ->union($tasks);

        $querySql = $query->toSql();
        $result = Task::rightJoin(DB::raw("($querySql) as a"), function ($join) {
            $join->on('tasks.id', '=', 'a.id');
        })
            ->mergeBindings($query)
            ->onlyTrashed()
            ->orderBy('a.created_at', 'desc')
            ->paginate($pageSize);

        return $this->response->paginator($result, new TaskTransformer());

    }

    public function show(Task $task)
    {
        return $this->response()->item($task, new TaskTransformer());
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
        DB::beginTransaction();
        try {
            $this->moduleUserRepository->delTaskModuleUser($participantIds, $task);
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

        DB::beginTransaction();
        try {
            $this->moduleUserRepository->addTaskModuleUser($participantIds, $task, ModuleUserType::PARTICIPANT);
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
        $now = Carbon::now();
        $array = [
            'status' => $status,
        ];
        switch ($status) {
            case TaskStatus::NORMAL:
                $array['complete_at'] = null;
                $array['stop_at'] = null;
                break;
            case TaskStatus::COMPLETE:
                if ($task->status == TaskStatus::TERMINATION)
                    return $this->response->errorBadRequest('终止不能转到完成');
                $array['complete_at'] = $now->toDateTimeString();
                break;
            case TaskStatus::TERMINATION:
                if ($task->status == TaskStatus::COMPLETE)
                    return $this->response->errorBadRequest('完成不能转到终止');
                $array['stop_at'] = $now->toDateTimeString();
                break;
        }

        DB::beginTransaction();
        try {
            $task->update($array);
            //TODO 操作日志
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('操作失败');
        }
        DB::commit();
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
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('操作失败');
        }
        DB::commit();
        if (!$task->privacy)
            return $this->response->noContent();
        return $this->response->accepted();
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
        DB::beginTransaction();
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
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('解除关联任务失败');
        }
        DB::commit();
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
        DB::beginTransaction();
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
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('关联任务失败');
            }
        }
        DB::commit();
        return $this->response->created();
    }

    public function deletePrincipal(Request $request, Task $task)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {
            if ($task->principal_id) {
                $task->principal_id = null;
                $task->save();
                //TODO 操作日志

                return $this->response->accepted();
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->noContent();
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function cancelTime(TaskCancelTimeRequest $request, Task $task)
    {
        $payload = $request->all();
        $type = $payload['type'];
        DB::beginTransaction();
        try {
            $task->update([$type => null]);
            //TODO 操作日志
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('取消失败');
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function update(TaskUpdateRequest $request, Task $task)
    {
        $payload = $request->all();

        $array = [];

        if ($request->has('title')) {
            $array['title'] = $payload['title'];
        }

        if ($request->has('desc')) {
            $array['desc'] = $payload['desc'];
        }

        if ($request->has('principal_id')) {
            try {
                $principalId = hashid_decode($payload['principal_id']);
                User::findOrFail($principalId);
                $array['principal_id'] = $principalId;
            } catch (Exception $e) {
                return $this->response->errorBadRequest();
            }
        }

        if ($request->has('priority')) {
            $array['priority'] = $payload['priority'];
        }

        if ($request->has('start_at')) {
            $array['start_at'] = $payload['start_at'];
        }

        if ($request->has('end_at')) {
            $array['end_at'] = $payload['end_at'];
        }

        try {
            if (count($array) == 0)
                return $this->response->noContent();

            $task->update($array);
            //TODO 操作日志
        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }

        return $this->response->accepted();
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
                $this->moduleUserRepository->addTaskModuleUser($payload['participant_ids'], $task, ModuleUserType::PARTICIPANT);
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
