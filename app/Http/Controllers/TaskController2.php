<?php

namespace App\Http\Controllers;

use App\AffixType;
use App\Events\OperateLogEvent;
use App\Http\Requests\Task\AddRelateTaskRequest;
use App\Http\Requests\Task\FilterTaskRequest;
use App\Http\Requests\TaskCancelTimeRequest;
use App\Http\Requests\TaskRequest;
use App\Http\Requests\TaskStatusRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Transformers\TaskTransformer;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Star;
use App\Models\Task;
use App\Models\TaskRelate;
use App\Models\TaskResource;
use App\Models\TaskType;
use App\Models\Trail;
use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\Repositories\AffixRepository;
use App\Repositories\ModuleUserRepository;
use App\Repositories\ScopeRepository;
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
    protected $affixRepository;

    public function __construct(ModuleUserRepository $moduleUserRepository, AffixRepository $affixRepository)
    {
        $this->moduleUserRepository = $moduleUserRepository;
        $this->affixRepository = $affixRepository;
    }

    public function index(Request $request)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $tasks = Task::where(function($query) use ($request, $payload) {
            if ($request->has('keyword'))
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('type_id'))
                $query->where('type_id', hashid_decode($payload['type_id']));
            if ($request->has('status'))
                $query->where('status', $payload['status']);

        })->searchData()->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($tasks, new TaskTransformer());
    }

    public function tasksAll(Request $request,Task $task)
    {
        $payload = $request->all();
        $data = $task->get()
            ->searchData()
            ->toArray();
        $dataArr = array();
        if(!empty($data)){
            foreach ($data as $k=>$value){
                $dataArr['id'] = hashid_encode($value['id']);
                $dataArr['title'] = $value['title'];
                $Arr['data'][] = $dataArr;
            }
        }else{
            $Arr['data'][]=$dataArr;
        }

        return $Arr;

    }

    public function myAll(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', 0);

        $tasks = DB::table('tasks')->select('tasks.*');
        switch ($status) {
            case 1://进行中
                $tasks->where('status', TaskStatus::NORMAL);
                break;
            case 2://完成
                $tasks->where('status', TaskStatus::COMPLETE);
                break;
            case 3://终止
                $tasks->where('status', TaskStatus::TERMINATION);
                break;
            default:
                break;
        }

        $tasks->Where(function ($query) use ($user) {
            $query->where('creator_id', $user->id)->orWhere('principal_id', $user->id);
        });

        $query = DB::table('tasks')->select('tasks.*')->join('module_users', function ($join) use ($user) {
            $join->on('module_users.moduleable_id', '=', 'tasks.id')
                ->where('module_users.moduleable_type', ModuleableType::TASK)
                ->where('module_users.user_id', $user->id);
        });
        switch ($status) {
            case 1://进行中
                $query->where('status', TaskStatus::NORMAL);
                break;
            case 2://完成
                $query->where('status', TaskStatus::COMPLETE);
                break;
            case 3://终止
                $query->where('status', TaskStatus::TERMINATION);
                break;
            default:
                break;
        }

        $query->union($tasks);

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
        //获取可查询用户的数据
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId === null){
            return $this->response->errorInternal("没有恢复任何数据的权限");
        }
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));
        //负责人和创建人是当前登录用户的任务
        $tasks = DB::table('tasks')->select('tasks.*')->where('creator_id', $user->id)->orWhere('principal_id', $user->id);

        //参与人是当前用户的任务
        $query = DB::table('tasks')->select('tasks.*')->join('module_users', function ($join) use ($user) {
            $join->on('module_users.moduleable_id', '=', 'tasks.id')
                ->where('module_users.moduleable_type', ModuleableType::TASK)
                ->where('module_users.user_id', $user->id);
        })
            ->union($tasks);

        $querySql = $query->toSql();
        //参与人与创建人与参与人是当前用户的任务
        $result = Task::rightJoin(DB::raw("($querySql) as a"), function ($join) {
            $join->on('tasks.id', '=', 'a.id');
        })->where(function ($query)use ($arrUserId){
            //限制查询数据范围
            if(count($arrUserId) > 0){
                $query->whereIn('tasks.creator_id',$arrUserId)
                    ->orWhereIn('tasks.principal_id',$arrUserId);
            }
        })
            ->mergeBindings($query)
            ->onlyTrashed()//只查询已删除的用户
            ->orderBy('a.created_at', 'desc')
            ->paginate($pageSize);

        return $this->response->paginator($result, new TaskTransformer());

    }

    public function my(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', 0);
        $type = $request->get('type', 1);

        $query = Task::select('tasks.*');

        switch ($type) {
            case 2://我参与
                $query = $user->participantTasks();
                break;
            case 3://我负责
                $query->where('principal_id', $user->id);
                break;
            case 1://我创建
            default:
                $query->where('creator_id', $user->id);
                break;
        }
        switch ($status) {
            case 1://进行中
                $query->where('status', TaskStatus::NORMAL);
                break;
            case 2://完成
                $query->where('status', TaskStatus::COMPLETE);
                break;
            case 3://终止
                $query->where('status', TaskStatus::TERMINATION);
                break;
            default:
                break;
        }
        $tasks = $query->createDesc()->paginate($pageSize);

        return $this->response->paginator($tasks, new TaskTransformer());
    }

    public function findModuleTasks(Request $request, Project $project, Client $client, Star $star, Trail $trail, Blogger $blogger)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));

        if ($project && $project->id) {
            $query = $project->tasks();
        } else if ($client && $client->id) {
            $query = $client->tasks();
        } else if ($star && $star->id) {
            $query = $star->tasks();
        } else if ($trail && $trail->id) {
            $query = $trail->tasks();
        } else if ($blogger && $blogger->id) {
            $query = $blogger->tasks();
        }
        //TODO 还有其他模块
        $tasks = $query->where('privacy', false)->paginate($pageSize);

        return $this->response->paginator($tasks, new TaskTransformer());
    }

    public function show(Task $task)
    {
        $arrUserId = (new ScopeRepository())->getDataViewUsers();

        if($arrUserId === null  || (count($arrUserId)!=0 && !in_array($task->creator_id,$arrUserId) && !in_array($task->principal_id,$arrUserId))){
            return $this->response->errorInternal("你没有查看该任务的权限");
        }
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
        //获取项目的参与者
        $res = $task->participants()->get();
        $power = (new ScopeRepository())->checkMangePower($task->creator_id,$task->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有编辑该任务状态的权限");
        }
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
        //获取项目的参与者
        $res = $task->participants()->get();
        $power = (new ScopeRepository())->checkMangePower($task->creator_id,$task->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有恢复该任务的权限");
        }
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
        //获取项目的参与者
        $res = $task->participants()->get();
        $power = (new ScopeRepository())->checkMangePower($task->creator_id,$task->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有删除该任务的权限");
        }
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
        //获取项目的参与者
        $res = $task->participants()->get();
        $power = (new ScopeRepository())->checkMangePower($task->creator_id,$task->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有设置该任务隐私的权限");
        }
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
    public function relieveResource(Request $request, Project $project, Star $star, Client $client, Trail $trail, Blogger $blogger, Task $task)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {
            $type = 0;
            if ($project && $project->id) {
                $type = ResourceType::PROJECT;
                $resourceable_id = $project->id;
                $resourceable_type = ModuleableType::PROJECT;
                $title = '项目';
                $start = $project->title;
            } else if ($star && $star->id) {
                $type = ResourceType::STAR;
                $resourceable_id = $star->id;
                $resourceable_type = ModuleableType::STAR;
                $title = '艺人';
                $start = $star->name;
            } else if ($client && $client->id) {
                $type = ResourceType::CLIENT;
                $resourceable_id = $client->id;
                $resourceable_type = ModuleableType::CLIENT;
                $title = '客户';
                $start = $client->company;
            } else if ($trail && $trail->id) {
                $type = ResourceType::TRAIL;
                $resourceable_id = $trail->id;
                $resourceable_type = ModuleableType::TRAIL;
                $title = '销售线索';
                $start = $client->title;
            } else if ($blogger && $blogger->id) {
                $type = ResourceType::BLOGGER;
                $resourceable_id = $blogger->id;
                $resourceable_type = ModuleableType::BLOGGER;
                $title = '博主';
                $start = $blogger->nickname;
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
    public function relevanceResource(Request $request, $model)
    {
        $payload = $request->all();
        DB::beginTransaction();
        if (!$model->task_pid) {//子任务不能关联资源
            try {
                $array = [
                    'task_id' => $model->id,
                ];

                $type = 0;
                if ($model instanceof Project && $model->id) {
                    $type = ResourceType::PROJECT;
                    $array['resourceable_id'] = $model->id;
                    $array['resourceable_type'] = ModuleableType::PROJECT;
                    $title = '项目';
                    $start = $model->title;
                } else if ($model instanceof Star && $model->id) {
                    $type = ResourceType::STAR;
                    $array['resourceable_id'] = $model->id;
                    $array['resourceable_type'] = ModuleableType::STAR;
                    $title = '艺人';
                    $start = $model->name;
                } else if ($model instanceof Client && $model->id) {
                    $type = ResourceType::CLIENT;
                    $array['resourceable_id'] = $model->id;
                    $array['resourceable_type'] = ModuleableType::CLIENT;
                    $title = '客户';
                    $start = $model->company;
                } else if ($model instanceof Trail && $model->id) {
                    $type = ResourceType::TRAIL;
                    $array['resourceable_id'] = $model->id;
                    $array['resourceable_type'] = ModuleableType::TRAIL;
                    $title = '销售线索';
                    $start = $model->title;
                } else if ($model instanceof Blogger && $model->id) {
                    $type = ResourceType::BLOGGER;
                    $array['resourceable_id'] = $model->id;
                    $array['resourceable_type'] = ModuleableType::BLOGGER;
                    $title = '博主';
                    $start = $model->nickname;
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
        //获取项目的参与者
        $res = $task->participants()->get();
        $power = (new ScopeRepository())->checkMangePower($task->creator_id,$task->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有删除该任务负责人的权限");
        }
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
        //获取项目的参与者
        $res = $task->participants()->get();
        $power = (new ScopeRepository())->checkMangePower($task->creator_id,$task->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有取消该任务的权限");
        }
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
        dump($request->route("task"));
        dd($task);
        //获取项目的参与者
        $res = $task->participants()->get();
        $power = (new ScopeRepository())->checkMangePower($task->creator_id,$task->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有编辑该任务的权限");
        }
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
            //获取项目的参与者
            $res = $pTask->participants()->get();
            $power = (new ScopeRepository())->checkMangePower($pTask->creator_id,$pTask->principal_id,array_column($res->toArray(),'id'));
            if(!$power){
                return $this->response->errorInternal("你没有给该任务增加子任务的权限");
            }
            if ($pTask->task_pid)
                return $this->response->errorBadRequest('子任务不支持多级子任务');
            $payload['task_pid'] = $pTask->id;
        }

        //验证 type
        if ($request->has('type') && $payload['type'] != 0) {
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
                if ($request->has('resource_type') && $request->has('resourceable_id')) {
                    $resourceType = $payload['resource_type'];
                    $resourceableId = hashid_decode($payload['resourceable_id']);
                    $resource = Resource::where('type', $resourceType)->first();
                    if ($resource) {
                        $array = [
                            'task_id' => $task->id,
                            'resource_id' => $resource->id,
                        ];
                        switch ($resource->type) {
                            case ResourceType::BLOGGER:
                                $blogger = Blogger::findOrFail($resourceableId);
                                $array['resourceable_id'] = $blogger->id;
                                $array['resourceable_type'] = ModuleableType::BLOGGER;
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
                            case ResourceType::CLIENT:
                                $client = Client::findOrFail($resourceableId);
                                $array['resourceable_id'] = $client->id;
                                $array['resourceable_type'] = ModuleableType::CLIENT;
                                break;
                            case ResourceType::TRAIL:
                                $trail = Trail::findOrFail($resourceableId);
                                $array['resourceable_id'] = $trail->id;
                                $array['resourceable_type'] = ModuleableType::TRAIL;
                                break;
                            //TODO
                        }

                        TaskResource::create($array);
                        // 操作日志 ...
                    } else {
                        throw new Exception('没有这个类型');
                    }
                }
            }

            if ($request->has('affix') && count($request->get('affix'))) {
                $affixes = $request->get('affix');
                foreach ($affixes as $affix) {
                    try {
                        $this->affixRepository->addAffix($user, $task, $affix['title'], $affix['url'], $affix['size'], AffixType::DEFAULT);
                        // 操作日志 ...
                    } catch (Exception $e) {
                    }
                }
            }

            //添加参与人
            if ($request->has('participant_ids')) {
                $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $task, ModuleUserType::PARTICIPANT);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->item(Task::find($task->id), new TaskTransformer());
//        return $this->response->created();
    }

    public function filter(FilterTaskRequest $request)
    {
        //获取可查询用户的数据
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId === null){
            return $this->response->errorInternal("没有查看数据的权限");
        }
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $tasks = Task::where(function($query) use ($request, $payload,$arrUserId) {
            if ($request->has('keyword'))
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('type_id'))
                $query->where('type_id', hashid_decode($payload['type_id']));
            if ($request->has('status'))
                $query->where('status', $payload['status']);
            //限制查询数据范围
            if(count($arrUserId) > 0){
                $query->whereIn('creator_id',$arrUserId)
                    ->orWhereIn('principal_id',$arrUserId);
            }
        })->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($tasks, new TaskTransformer());
    }

    public function addRelates(AddRelateTaskRequest $request, Task $task)
    {
        DB::beginTransaction();
        try {

            if ($request->has('tasks')) {
                TaskRelate::where('task_id', $task->id)->where('moduleable_type', ModuleableType::TASK)->delete();
                $tasks = $request->get('tasks');
                foreach ($tasks as $value) {
                    $id = hashid_decode($value);
                    if (Task::find($id))
                        TaskRelate::create([
                            'task_id' => $task->id,
                            'moduleable_id' => $id,
                            'moduleable_type' => ModuleableType::TASK,
                        ]);
                }
            }

            if ($request->has('projects')) {
                TaskRelate::where('task_id', $task->id)->where('moduleable_type', ModuleableType::PROJECT)->delete();
                $projects = $request->get('projects');
                foreach ($projects as $value) {
                    $id = hashid_decode($value);
                    if (Project::find($id))
                        TaskRelate::create([
                            'task_id' => $task->id,
                            'moduleable_id' => $id,
                            'moduleable_type' => ModuleableType::PROJECT,
                        ]);
                }
            }

        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('创建关联失败');
        }
        DB::commit();
        return $this->response->accepted();
    }
}