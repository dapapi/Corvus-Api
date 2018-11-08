<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\TaskCancelTimeRequest;
use App\Http\Requests\TaskRequest;
use App\Http\Requests\TaskStatusRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Transformers\TaskTransformer;
use App\Models\Client;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Star;
use App\Models\Task;
use App\Models\TaskResource;
use App\Models\TaskType;
use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\Repositories\ModuleUserRepository;
use App\ResourceType;
use App\TaskPriorityStatus;
use App\TaskStatus;
use App\User;
use Carbon\Carbon;
use Exception;
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
        $pageSize = $request->get('page_size', config('app.page_size'));

        $tasks = Task::createDesc()->paginate($pageSize);

        return $this->response->paginator($tasks, new TaskTransformer());
    }

    public function myAll(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));

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

    public function my(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', 1);
        $type = $request->get('type', 0);

        $query = Task::select('tasks.*');

        switch ($status) {
            case 2://我负责
                $query->where('principal_id', $user->id);
                break;
            case 3://我参与
                $query = $user->participantTasks();
                break;
            case 1://我创建
            default:
                $query->where('creator_id', $user->id);
                break;
        }
        switch ($type) {
            case 1://进行中
                $query->where('status', TaskStatus::NORMAL);
                break;
            case 2://完成
                $query->where('status', TaskStatus::COMPLETE);
                break;
        }
        $tasks = $query->createDesc()->paginate($pageSize);

        return $this->response->paginator($tasks, new TaskTransformer());
    }

    public function findModuleTasks(Request $request, Project $project, Client $client, Star $star)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));
        if ($project && $project->id) {
            $tasks = $project->tasks()->paginate($pageSize);
        } else if ($client && $client->id) {
            $tasks = $client->tasks()->paginate($pageSize);
        } else if ($star && $star->id) {
            $tasks = $star->tasks()->paginate($pageSize);
        }
        //TODO 还有其他模块
        return $this->response->paginator($tasks, new TaskTransformer());
    }


    public function recycleBin(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));

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
        // 操作日志
        $operate = new OperateEntity([
            'obj' => $task,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
        return $this->response()->item($task, new TaskTransformer());
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
        $method = OperateLogMethod::ACTIVATE;
        switch ($status) {
            case TaskStatus::NORMAL:
                $array['complete_at'] = null;
                $array['stop_at'] = null;
                $method = OperateLogMethod::ACTIVATE;
                break;
            case TaskStatus::COMPLETE:
                if ($task->status == TaskStatus::TERMINATION)
                    return $this->response->errorBadRequest('终止不能转到完成');
                $array['complete_at'] = $now->toDateTimeString();

                $method = OperateLogMethod::COMPLETE;
                break;
            case TaskStatus::TERMINATION:
                if ($task->status == TaskStatus::COMPLETE)
                    return $this->response->errorBadRequest('完成不能转到终止');
                $array['stop_at'] = $now->toDateTimeString();

                $method = OperateLogMethod::TERMINATION;
                break;
        }

        DB::beginTransaction();
        try {
            $task->update($array);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $task,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => $method,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('操作失败');
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function recoverRemove(Task $task)
    {
        DB::beginTransaction();
        try {
            $deletedAt = $task->deleted_at;
            if (!$task->task_pid) {
                //删除所有子任务
                $subtasks = Task::where('task_pid', $task->id)->onlyTrashed()->where('deleted_at', $deletedAt)->get();
                foreach ($subtasks as $subtask) {
                    $subtask->restore();
                    // 操作日志
                    $operate = new OperateEntity([
                        'obj' => $subtask,
                        'title' => null,
                        'start' => null,
                        'end' => null,
                        'method' => OperateLogMethod::RECOVER,
                    ]);
                    event(new OperateLogEvent([
                        $operate,
                    ]));
                }
            }
            $task->restore();
            if ($deletedAt) {
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $task,
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::RECOVER,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('恢复任务失败');
        }
        DB::commit();
        return $this->response->noContent();
    }

    public function remove(Task $task)
    {
        DB::beginTransaction();
        try {
            $deletedAt = $task->deleted_at;
            if (!$deletedAt) {
                if (!$task->task_pid) {
                    //删除所有子任务
                    $subtasks = Task::where('task_pid', $task->id)->get();
                    foreach ($subtasks as $subtask) {
                        $subtask->delete();
                        // 操作日志
                        $operate = new OperateEntity([
                            'obj' => $subtask,
                            'title' => null,
                            'start' => null,
                            'end' => null,
                            'method' => OperateLogMethod::DELETE,
                        ]);
                        event(new OperateLogEvent([
                            $operate,
                        ]));
                    }
                }
                $task->delete();
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $task,
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::DELETE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();
        return $this->response->noContent();
    }

    /**
     * 隐私切换
     * @param Task $task
     * @return \Dingo\Api\Http\Response|void
     */
    public function togglePrivacy(Task $task)
    {
        DB::beginTransaction();
        try {
            $method = $task->privacy = !$task->privacy;
            $task->save();
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $task,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => $method ? OperateLogMethod::PRIVACY : OperateLogMethod::PUBLIC,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
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
    public function relieveResource(Request $request, Project $project, Star $star, Task $task)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {
            $type = 0;
            if ($project && $project->id) {
                $type = ResourceType::PROJECT;
                $project = Project::findOrFail($project->id);
                $resourceable_id = $project->id;
                $resourceable_type = ModuleableType::PROJECT;
                $title = '项目';
                $start = $project->title;
            } else if ($star && $star->id) {
                $type = ResourceType::STAR;
                $star = Star::findOrFail($star->id);
                $resourceable_id = $star->id;
                $resourceable_type = ModuleableType::STAR;
                $title = '艺人';
                $start = $star->name;
            } else {
                //TODO 处理其他资源
                $title = '其他';
                $start = '其他模块';
            }
            $resource = Resource::where('type', $type)->first();

            $taskResource = TaskResource::where('task_id', $task->id)
                ->where('resourceable_id', $resourceable_id)
                ->where('resourceable_type', $resourceable_type)
                ->where('resource_id', $resource->id)
                ->first();
            if ($taskResource) {
                $taskResource->delete();
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $task,
                    'title' => $title,
                    'start' => $start,
                    'end' => null,
                    'method' => OperateLogMethod::UN_RELEVANCE_RESOURCE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
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
    public function relevanceResource(Request $request, Project $project, Star $star, Task $task)
    {
        $payload = $request->all();
        DB::beginTransaction();
        if (!$task->task_pid) {//子任务不能关联资源
            try {
                $array = [
                    'task_id' => $task->id,
                ];

                $type = 0;
                if ($project && $project->id) {
                    $type = ResourceType::PROJECT;
                    $project = Project::findOrFail($project->id);
                    $array['resourceable_id'] = $project->id;
                    $array['resourceable_type'] = ModuleableType::PROJECT;
                    $title = '项目';
                    $start = $project->title;
                } else if ($star && $star->id) {
                    $type = ResourceType::STAR;
                    $star = Star::findOrFail($star->id);
                    $array['resourceable_id'] = $star->id;
                    $array['resourceable_type'] = ModuleableType::STAR;
                    $title = '艺人';
                    $start = $star->name;
                } else {
                    //TODO 处理其他资源
                    $title = '其他';
                    $start = '其他模块';
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
                    // 操作日志
                    $operate = new OperateEntity([
                        'obj' => $task,
                        'title' => $title,
                        'start' => $start,
                        'end' => null,
                        'method' => OperateLogMethod::RELEVANCE_RESOURCE,
                    ]);
                    event(new OperateLogEvent([
                        $operate,
                    ]));
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
                $user = User::where('id', $task->principal_id)->first();
                $start = $user->name;
                $task->principal_id = null;
                $task->save();
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $task,
                    'title' => null,
                    'start' => $start,
                    'end' => null,
                    'method' => OperateLogMethod::DEL_PRINCIPAL,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
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
            $title = '开始时间';
            $start = $task->start_at;
            if ($type == 'end_at') {
                $title = '结束时间';
                $start = $task->end_at;
            }
            if ($start) {
                $task->update([$type => null]);
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $task,
                    'title' => $title,
                    'start' => $start,
                    'end' => null,
                    'method' => OperateLogMethod::CANCEL,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            } else {
                return $this->response->noContent();
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('取消失败');
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function edit(TaskUpdateRequest $request, Task $task)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        $array = [];

        $arrayOperateLog = [];

        if ($request->has('title')) {
            $array['title'] = $payload['title'];
            if ($array['title'] != $task->title) {
                $operateTitle = new OperateEntity([
                    'obj' => $task,
                    'title' => '标题',
                    'start' => $task->title,
                    'end' => $array['title'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateTitle;
            }
        }

        if ($request->has('desc')) {
            $array['desc'] = $payload['desc'];

            $operateDesc = new OperateEntity([
                'obj' => $task,
                'title' => '描述',
                'start' => $task->desc,
                'end' => $array['desc'],
                'method' => OperateLogMethod::UPDATE,
            ]);
            $arrayOperateLog[] = $operateDesc;
        }

        if ($request->has('type')) {
            $departmentId = $user->department()->first()->id;
            $typeId = hashid_decode($payload['type']);
            $taskType = TaskType::where('id', $typeId)->where('department_id', $departmentId)->first();
            if ($taskType) {
                $array['type_id'] = $taskType->id;
                $start = null;
                if ($task->type) {
                    $start = $task->type->title;
                }
                $end = $taskType->title;

                $operateType = new OperateEntity([
                    'obj' => $task,
                    'title' => '类型',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                if ($task->type && $task->type->id == $taskType->id) {
                    unset($array['type_id']);
                } else {
                    $arrayOperateLog[] = $operateType;
                }
            } else {
                return $this->response->errorBadRequest('你所在的部门下没有这个类型');
            }
        }

        if ($request->has('principal_id')) {
            try {
                $currentPrincipalUser = User::find($task->principal_id);
                $start = null;
                if ($currentPrincipalUser)
                    $start = $currentPrincipalUser->name;

                $principalId = hashid_decode($payload['principal_id']);
                $principalUser = User::findOrFail($principalId);
                $array['principal_id'] = $principalId;

                if ($currentPrincipalUser) {
                    if ($currentPrincipalUser->id != $array['principal_id']) {
                        $operatePrincipal = new OperateEntity([
                            'obj' => $task,
                            'title' => '负责人',
                            'start' => $start,
                            'end' => $principalUser->name,
                            'method' => OperateLogMethod::UPDATE,
                        ]);
                        $arrayOperateLog[] = $operatePrincipal;
                    } else {
                        unset($arrayOperateLog['principal_id']);
                    }
                }
            } catch (Exception $e) {
                return $this->response->errorBadRequest();
            }
        }

        if ($request->has('priority')) {
            $array['priority'] = $payload['priority'];
            if ($array['priority'] != $task->priority) {
                $start = TaskPriorityStatus::getStr($task->priority);
                $end = TaskPriorityStatus::getStr($array['priority']);

                $operatePriority = new OperateEntity([
                    'obj' => $task,
                    'title' => '优先级',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operatePriority;
            } else {
                unset($array['priority']);
            }
        }

        if ($request->has('start_at')) {
            $array['start_at'] = $payload['start_at'];
            $start = $task->start_at;
            $end = $array['start_at'];

            if ($start != $end) {
                $operateStartAt = new OperateEntity([
                    'obj' => $task,
                    'title' => '开始时间',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateStartAt;
            } else {
                unset($array['start_at']);
            }
        }

        if ($request->has('end_at')) {
            $array['end_at'] = $payload['end_at'];
            $start = $task->end_at;
            $end = $array['end_at'];
            if ($start != $end) {
                $operateStartAt = new OperateEntity([
                    'obj' => $task,
                    'title' => '结束时间',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateStartAt;
            } else {
                unset($array['end_at']);
            }
        }
        DB::beginTransaction();
        try {
            if (count($array) == 0)
                return $this->response->noContent();

            $task->update($array);
            // 操作日志
            event(new OperateLogEvent($arrayOperateLog));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();

        return $this->response->accepted();
    }

    public function store(TaskRequest $request, Task $pTask)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        unset($payload['status']);
        unset($payload['complete_at']);
        unset($payload['stop_at']);
        unset($payload['type_id']);

        $payload['creator_id'] = $user->id;

        if ($request->has('principal_id')) {
            try {
                $principalId = hashid_decode($payload['principal_id']);
                User::findOrFail($principalId);
                $payload['principal_id'] = $principalId;
            } catch (Exception $e) {
                return $this->response->errorBadRequest('负责人错误');
            }
        }

        if ($pTask->id) {
            if ($pTask->task_pid)
                return $this->response->errorBadRequest('子任务不支持多级子任务');
            $payload['task_pid'] = $pTask->id;
        }

        //验证 type
        if ($request->has('type')) {
            $departmentId = $user->department()->first()->id;
            $typeId = hashid_decode($payload['type']);
            $taskType = TaskType::where('id', $typeId)->where('department_id', $departmentId)->first();
            if ($taskType) {
                $payload['type_id'] = $taskType->id;
            } else {
                return $this->response->errorBadRequest('你所在的部门下没有这个类型');
            }
        }

        if (!$request->has('privacy')) {
            $payload['privacy'] = false;
        }

        DB::beginTransaction();
        try {
            $task = Task::create($payload);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $task,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));

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
                        case ResourceType::STAR:
                            $star = Star::findOrFail($resourceableId);
                            $array['resourceable_id'] = $star->id;
                            $array['resourceable_type'] = ModuleableType::STAR;
                            break;
                        case ResourceType::PROJECT:
                            $project = Project::findOrFail($resourceableId);
                            $array['resourceable_id'] = $project->id;
                            $array['resourceable_type'] = ModuleableType::PROJECT;
                            break;
                        //TODO
                        default:
                            return $this->response->errorBadRequest('关联任务失败');
                    }

                    TaskResource::create($array);
                    // 操作日志 ...
                }
            }

            //添加参与人
            if ($request->has('participant_ids')) {
                $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $task, null, null, ModuleUserType::PARTICIPANT);
            }
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->item(Task::find($task->id), new TaskTransformer());
//        return $this->response->created();
    }

}
