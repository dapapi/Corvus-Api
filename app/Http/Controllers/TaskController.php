<?php

namespace App\Http\Controllers;

use App\AffixType;
use App\Events\OperateLogEvent;
use App\Events\TaskDataChangeEvent;
use App\Events\TaskMessageEvent;
use App\Helper\Common;
use App\Http\Requests\Task\AddRelateTaskRequest;
use App\Http\Requests\Task\FilterTaskRequest;
use App\Http\Requests\TaskCancelTimeRequest;
use App\Http\Requests\TaskRequest;
use App\Http\Requests\TaskStatusRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Transformers\DashboardModelTransformer;
use App\Http\Transformers\TaskTransformer;
use App\Http\Transformers\ClientTaskTransformer;

use App\Models\Blogger;
use App\Models\Client;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Star;
use App\Models\Task;
use App\Models\TaskRelate;
use App\Models\TaskResource;
use App\Repositories\TaskRepository;

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
use App\TriggerPoint\TaskTriggerPoint;
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
        $user = Auth::guard("api")->user();
        $my = $request->get('my', 0);
        $pageSize = $request->get('page_size', config('app.page_size'));

        $query = Task::select('tasks.*');
        switch ($my) {
            case 2://我参与
                $query = $user->participantTasks();
                break;
            case 3://我负责
                $query->where('principal_id', $user->id);
                break;
            case 4://我分配
                $query->where('creator_id', $user->id)->where('principal_id', '!=', $user->id);
                break;
            case 1://我创建
                $query->where('creator_id', $user->id);
                break;
            default:
                break;
        }
        $tasks = $query->where(function ($query) use ($request, $payload) {
            if ($request->has('keyword'))
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('type_id'))
                $query->where('type_id', hashid_decode($payload['type_id']));
            if ($request->has('status'))
                $query->where('status', $payload['status']);
            if ($request->has('user')){
                $userId = hashid_decode($payload['user']);
                $query->where('principal_id', $userId);
            }
            if ($request->has('department')){
                $userIds = array();
                $userIds = $this->getDepartmentUserIds($payload['department']);
                $query->whereIn('principal_id', $userIds);
            }

        })->searchData()->orderBy('updated_at', 'desc')->paginate($pageSize);//created_at

        return $this->response->paginator($tasks, new TaskTransformer());
    }


    public function indexAllDemo(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard("api")->user();
        $userId = $user->id;
        $my = $request->get('my',0);
        $pageSize = $request->get('page_size', config('app.page_size'));

      $query = Task::select('tasks.id','tasks.title as task_name','tasks.status','tasks.end_at','tts.title','tr.resourceable_id','tr.resourceable_type','tr.resource_id','users.name','tasks.adj_id')
               ->leftjoin('task_resources as tr', function ($join) {
                   $join->on('tr.task_id', '=', 'tasks.id');
                 })
                ->join('users', function ($join) {
                $join->on('tasks.principal_id', '=', 'users.id');
                })
              ->leftjoin('task_types as tts', function ($join) {
                  $join->on('tasks.type_id', '=', 'tts.id');
              });


        $tasks = $query->where(function($query) use ($request, $payload) {
            if ($request->has('keyword'))
                $query->where('tasks.title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('type_id'))
                $query->where('type_id', hashid_decode($payload['type_id']));
            if ($request->has('status'))
                $query->where('tasks.status', $payload['status']);
            if ($request->has('user')){
                $userId = hashid_decode($payload['user']);
                $query->where('tasks.principal_id', $userId);
            }
            if ($request->has('department')){
                $userIds = array();
                $userIds = $this->getDepartmentUserIds($payload['department']);
                $query->whereIn('tasks.principal_id', $userIds);
            }else{
                $query->whereRaw('1=1');
            }
        })->searchData()->orWhereRaw("FIND_IN_SET($user->id,tasks.adj_id)")->orderBy('tasks.updated_at', 'desc')->paginate($pageSize);//created_at

        foreach ($tasks as &$value){
            $value['id'] = hashid_encode($value['id']);
            if($value['resourceable_type'] == 'star'){
                $resource_name = DB::table('stars')->where('stars.id',$value['resourceable_id'])->select('name')->first();
                $resource_type = DB::table('resources')->where('resources.id',$value['resource_id'])->select('title')->first();

            }elseif($value['resourceable_type'] == 'project'){
                $value['resource_name'] = DB::table('projects')->where('projects.id',$value['resourceable_id'])->select('title as name')->first();
                $value['resource_type'] = DB::table('resources')->where('resources.id',$value['resource_id'])->select('title')->first();

            }elseif($value['resourceable_type'] == 'blogger'){
                $value['resource_name'] = DB::table('bloggers')->where('bloggers.id',$value['resourceable_id'])->select('nickname as name')->first();
                $value['resource_type'] = DB::table('resources')->where('resources.id',$value['resource_id'])->select('title')->first();

            }elseif($value['resourceable_type'] == 'trail'){
                $value['resource_name'] = DB::table('trails')->where('trails.id',$value['resourceable_id'])->select('title as name')->first();
                $value['resource_type'] = DB::table('resources')->where('resources.id',$value['resource_id'])->select('title')->first();

            }elseif($value['resourceable_type'] == 'client'){
                $value['resource_name'] = DB::table('clients')->where('clients.id',$value['resourceable_id'])->select('company as name')->first();
                $value['resource_type'] = DB::table('resources')->where('resources.id',$value['resource_id'])->select('title')->first();


            }
        }
        return $tasks;

    }
    public function indexAll(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard("api")->user();
        $userId = $user->id;
        $my = $request->get('my',0);

        $pageSize = $request->get('page_size', config('app.page_size'));

        $query = Task::select('tasks.id','tasks.title','users.icon_url','tasks.status','tasks.resource_name','tasks.resource_type_name as resource_type','tasks.principal_name','tasks.type_name','tasks.adj_id','tasks.end_at')
            ->join('users', function ($join) {
                 $join->on('users.id', '=', 'tasks.creator_id');
            });
        switch ($my) {
            case 2://我参与
                $query = $user->participantTasks();
                break;
            case 3://我负责
                $query->where('principal_id', $user->id);
                break;
            case 4://我分配
                $query->where('creator_id', $user->id)->where('principal_id','!=',$user->id);
                break;
            case 1://我创建
                $query->where('creator_id', $user->id);
                break;
            default:

                break;
        }

        $tasks = $query->where(function($query) use ($request, $payload,$my,$userId) {
            if ($request->has('keyword'))

                $query->where('tasks.title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('type_id'))
                $query->where('type_id', hashid_decode($payload['type_id']));
            if ($request->has('status'))
                $query->where('tasks.status', $payload['status']);
            if ($request->has('user')){
                $userId = hashid_decode($payload['user']);
                $query->where('tasks.principal_id', $userId);
            }
            if ($request->has('department')){
                $userIds = array();
                $userIds = $this->getDepartmentUserIds($payload['department']);
                $query->whereIn('tasks.principal_id', $userIds);
            }
            if($my ==0){
                $query->whereRaw('1=1');
                $query->orWhereRaw("FIND_IN_SET($userId,tasks.adj_id)");
            }
        })->searchData()->orderBy('tasks.updated_at', 'desc')->paginate($pageSize);//created_at
        foreach ($tasks as &$value) {
            $value['id'] = hashid_encode($value['id']);
        }
        return $tasks;

    }

    //获取子任务
    public function getChildTasks(Request $request,Task $task)
    {
        $payload = $request->all();
        $user = Auth::guard("api")->user();
        $userId = $user->id;
        $my = $request->get('my',0);
        $pageSize = $request->get('page_size', config('app.page_size'));

        $query = Task::select('tasks.id','tasks.title','tasks.status','tasks.principal_name','tasks.type_name','tasks.end_at');

        $tasks = $query->where(function($query) use ($request, $payload,$task) {

                 $query->where('task_pid',$task->id);

        })->orderBy('tasks.updated_at', 'desc')->paginate($pageSize);//created_at
        foreach ($tasks as &$value) {
            $value['id'] = hashid_encode($value['id']);
        }
        return $tasks;

    }





    public function getDepartmentUserIds($departmentId){
        $userIds = array();
        $departmentId = hashid_decode($departmentId);
        //查询部门id所有下级部门id
        $res = DB::select("select id from departments where find_in_set(id, getChildList($departmentId))");
        $resArr = json_decode(json_encode($res), true);
        $ids = array_column($resArr, 'id');
        //根据部门查询所有部门下userid
        $departmentUserIds = DB::table('department_user')->select('user_id')->whereIn('department_id',$ids)->get()->toArray();
        $departmentUserIdArr = json_decode(json_encode($departmentUserIds), true);
        $userIds = array_column($departmentUserIdArr, 'user_id');
        $uniqueUserIds = array_unique($userIds);

        return $uniqueUserIds;
    }


    public function tasksAll(Request $request, Task $task)
    {
        $payload = $request->all();
        $data = $task
            ->searchData()
            ->get()->toArray();
        $dataArr = array();
        if (!empty($data)) {
            foreach ($data as $k => $value) {
                $dataArr['id'] = hashid_encode($value['id']);
                $dataArr['title'] = $value['title'];
                $Arr['data'][] = $dataArr;
            }
        } else {
            //$Arr['data'][]=$dataArr;
            $Arr['data'] = $dataArr;
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

//        $tasks->Where(function ($query) use ($user) {
//            $query->where('creator_id', $user->id)->orWhere('principal_id', $user->id);
//        });

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
            ->searchData()
            ->orderBy('a.updated_at', 'desc')//created_at
            ->paginate($pageSize);

        return $this->response->paginator($result, new TaskTransformer());
    }

    public function myList(Request $request)
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
            case 4://我分配
                $query->where('creator_id', $user->id)->where('principal_id', '!=', $user->id);
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

        if ($request->has('keyword'))
            $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
        if ($request->has('type_id'))
            $query->where('type_id', hashid_decode($payload['type_id']));


        // $tasks = $query->createDesc()->paginate($pageSize);

        $tasks = $query->searchData()->orderBy('updated_at', 'desc')->paginate($pageSize);//created_at

        return $this->response->paginator($tasks, new TaskTransformer());
    }


    public function recycleBin(Request $request)
    {
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
        })->searchData()
            ->mergeBindings($query)
            ->onlyTrashed()//只查询已删除的用户
            ->orderBy('a.updated_at', 'desc')//created_at
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
            case 4://我分配
                $query->where('creator_id', $user->id)->where('principal_id', '!=', $user->id);
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
        $tasks = $query->paginate($pageSize);;

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
        $tasks = $query->searchData()->where('privacy', false)->paginate($pageSize);

        //获取任务完成数量
        $complete_count = $query->where('privacy', false)->where('status', TaskStatus::COMPLETE)->count();

        $request = $this->response->paginator($tasks, new ClientTaskTransformer());
        $request->addMeta("complete_count", $complete_count);
        return $request;
    }

    public function getClientTaskList(Request $request,Client $client)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));
        $query = $client->tasks();

        $tasks = $query->searchData()->where('privacy', false)->paginate($pageSize);
        //获取任务完成数量
        $complete_count = $query->where('privacy', false)->where('status', TaskStatus::COMPLETE)->count();

        $request = $this->response->paginator($tasks, new ClientTaskTransformer());
        $request->addMeta("complete_count",$complete_count);
        return $request;
    }

    public function getClientTaskNorma(Request $request,Client $client)
    {
        $task = DB::table('task_resources as ts')
            ->join('tasks', function ($join) {
                $join->on('ts.task_id', '=', 'tasks.id');
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'tasks.creator_id');
            })
            ->where('ts.resourceable_id', $client->id)->where('ts.resourceable_type', 'client')->where('tasks.status',1)->orderBy('tasks.created_at')
            ->select('tasks.id','tasks.title','tasks.status','tasks.end_at','users.name')
            ->limit(3)->get()->toArray();

        if($task){
            foreach ($task as &$value){
                $value->id = hashid_encode($value->id);
            }
        }
        return $task;

    }



    public function showDemo(Task $task,ScopeRepository $repository)
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
        //登录用户对线索编辑权限验证
        try{
            $user = Auth::guard("api")->user();
            //获取用户角色
            $role_list = $user->roles()->pluck('id')->all();
            $repository->checkPower("tasks/{id}",'put',$role_list,$task);
            $task->power = "true";
        }catch (Exception $exception){
            $task->power = "false";
        }
        return $this->response()->item($task, new TaskTransformer());
    }

    public function show(Task $task,ScopeRepository $repository,TaskRepository $taskRepository)
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
        //登录用户对线索编辑权限验证
        $user = Auth::guard("api")->user();
        try{
            //获取用户角色
            $role_list = $user->roles()->pluck('id')->all();
            $repository->checkPower("tasks/{id}",'put',$role_list,$task);
            $task->power = "true";
        }catch (Exception $exception){
            $task->power = "false";
        }
        $task->powers = $taskRepository->getPower($user,$task);
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

        if ($status == TaskStatus::COMPLETE) {
            $user = Auth::guard("api")->user();
            $authorization = $request->header()['authorization'][0];
            event(new TaskMessageEvent($task, TaskTriggerPoint::COMPLETE_TSAK, $authorization, $user));
        }
        //发送消息
//        DB::beginTransaction();
//        try {
//
//            $user = Auth::guard('api')->user();
//            $message = "";
//            switch ($status){
//                case TaskStatus::NORMAL:
//                    $message="任务状态转为正常";
//                    break;
//                case TaskStatus::COMPLETE:
//                    $message="任务完成";
//                    break;
//                case TaskStatus::TERMINATION:
//                    $message="任务终止";
//                    break;
//            }
//            $title = $user->name . $message;  //通知消息的标题
//            $subheading = $user->name . $message;
//            $module = Message::TASK;
//            $link = URL::action("TaskController@show", ["task" => $task->id]);
//            $data = [];
//            $data[] = [
//                "title" => '任务名称', //通知消息中的消息内容标题
//                'value' => $task->title,  //通知消息内容对应的值
//            ];
//            $principal = User::findOrFail($task->principal_id);
//            $data[] = [
//                'title' => '负责人',
//                'value' => $principal->name
//            ];
//
//            $recives = array_column($task->participants()->get()->toArray(),'id');
//            $recives[] = $task->creator_id;//创建人
//            $recives[] = $task->principal_id;//负责人
//            $authorization = $request->header()['authorization'][0];
//            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $recives,$task->id);
//            DB::commit();
//        }catch (Exception $e){
//            DB::rollBack();
//            Log::error($e);
//        }

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

                $taskResource = TaskResource::where('task_id', $model->id)
                    ->where('resourceable_id', $array['resourceable_id'])
                    ->where('resourceable_type', $array['resourceable_type'])
                    ->where('resource_id', $resource->id)
                    ->first();
                if (!$taskResource) {
                    TaskResource::create($array);
                    // 操作日志
                    $operate = new OperateEntity([
                        'obj' => $model,
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
        $oldTask = clone $task;
        $user = Auth::guard('api')->user();

        $array = [];

        $arrayOperateLog = [];

        if ($request->has('title')) {
            $array['title'] = $payload['title'];
//            if ($array['title'] != $task->title) {
//                $operateTitle = new OperateEntity([
//                    'obj' => $task,
//                    'title' => '标题',
//                    'start' => $task->title,
//                    'end' => $array['title'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateTitle;
//            }
        }

        if ($request->has('desc')) {
            $array['desc'] = $payload['desc'];

//            $operateDesc = new OperateEntity([
//                'obj' => $task,
//                'title' => '描述',
//                'start' => $task->desc,
//                'end' => $array['desc'],
//                'method' => OperateLogMethod::UPDATE,
//            ]);
//            $arrayOperateLog[] = $operateDesc;
        }

        if ($request->has('type')) {
            $departmentId = $user->department()->first()->id;
            $typeId = hashid_decode($payload['type']);
            $taskType = TaskType::where('id', $typeId)->where('department_id', $departmentId)->first();
            if ($taskType) {
                $array['type_id'] = $taskType->id;
//                $start = null;
//                if ($task->type) {
//                    $start = $task->type->title;
//                }
//                $end = $taskType->title;

//                $operateType = new OperateEntity([
//                    'obj' => $task,
//                    'title' => '类型',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
                if ($task->type && $task->type->id == $taskType->id) {
                    unset($array['type_id']);
                } else {
//                    $arrayOperateLog[] = $operateType;
                }
            } else {
                return $this->response->errorBadRequest('你所在的部门下没有这个类型');
            }
        }

        if ($request->has('principal_id')) {
            try {
                $currentPrincipalUser = User::find($task->principal_id);
                $start = null;
//                if ($currentPrincipalUser)
//                    $start = $currentPrincipalUser->name;

                $principalId = hashid_decode($payload['principal_id']);
//                $principalUser = User::findOrFail($principalId);
                $array['principal_id'] = $principalId;

                if ($currentPrincipalUser) {
                    if ($currentPrincipalUser->id != $array['principal_id']) {
//                        $operatePrincipal = new OperateEntity([
//                            'obj' => $task,
//                            'title' => '负责人',
//                            'start' => $start,
//                            'end' => $principalUser->name,
//                            'method' => OperateLogMethod::UPDATE,
//                        ]);
//                        $arrayOperateLog[] = $operatePrincipal;
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
//                $start = TaskPriorityStatus::getStr($task->priority);
//                $end = TaskPriorityStatus::getStr($array['priority']);
//
//                $operatePriority = new OperateEntity([
//                    'obj' => $task,
//                    'title' => '优先级',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operatePriority;
            } else {
                unset($array['priority']);
            }
        }

        //修改关联资源
        if ($request->has('resource_type')) {
            $resourceableId = hashid_decode($payload['resourceable_id']);
            $resourceType = $payload['resource_type'];
            $taskResource = TaskResource::where('task_id', $task->id)->first();
            if ($payload['code'] == 'bloggers') {
                $code = ModuleableType::BLOGGER;
            } elseif ($payload['code'] == 'stars') {
                $code = ModuleableType::STAR;
            } elseif ($payload['code'] == 'projects') {
                $code = ModuleableType::PROJECT;
            } elseif ($payload['code'] == 'clients') {
                $code = ModuleableType::CLIENT;
            } elseif ($payload['code'] == 'trails') {
                $code = ModuleableType::TRAIL;
            } else {
                return $this->response->errorInternal('上传类型不正确');
            }
            $resource = [
                'resource_id' => $resourceType,
                'resourceable_id' => $resourceableId,
                'resourceable_type' => $code,
            ];
            $taskResource->update($resource);
            unset($payload['code']);

        }

        if ($request->has('start_at')) {
            $array['start_at'] = $payload['start_at'];
            $start = $task->start_at;
            $end = $array['start_at'];

            if ($start != $end) {
//                $operateStartAt = new OperateEntity([
//                    'obj' => $task,
//                    'title' => '开始时间',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateStartAt;
            } else {
                unset($array['start_at']);
            }
        }

        if ($request->has('end_at')) {
            $array['end_at'] = $payload['end_at'];
            $start = $task->end_at;
            $end = $array['end_at'];
            if ($start != $end) {
//                $operateStartAt = new OperateEntity([
//                    'obj' => $task,
//                    'title' => '结束时间',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateStartAt;
                //修改日期 如果日期大于当前时间 状态为1正常 反之则状态为4 延期
                $endAt = strtotime($payload['end_at']);
                $currentAt = time();
                if ($endAt > $currentAt) {
                    $array['status'] = 1;

                } else {
                    $array['status'] = 4;
                }

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
//            event(new OperateLogEvent($arrayOperateLog));
            event(new TaskDataChangeEvent($oldTask, $task));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();
//        event(new dataChangeEvent($oldTask,$task));
        return $this->response->accepted();
    }

    //////////////////////
    public function taskEdit(TaskUpdateRequest $request, Task $task)
    {
        $payload = $request->all();
        $oldTask = clone $task;
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
//                    $arrayOperateLog[] = $operateType;
                }
            } else {
                return $this->response->errorBadRequest('你所在的部门下没有这个类型');
            }
        }

        if ($request->has('principal_id')) {
            try {
                $currentPrincipalUser = User::find($task->principal_id);
                $start = null;
//                if ($currentPrincipalUser)
//                    $start = $currentPrincipalUser->name;

                $principalId = hashid_decode($payload['principal_id']);
//                $principalUser = User::findOrFail($principalId);
                $userName = DB::table('users')->where('users.id', $principalId)->select('name')->first();
                $array['principal_name'] = $userName->name;
                $array['principal_id'] = $principalId;

                if ($currentPrincipalUser) {
                    if ($currentPrincipalUser->id != $array['principal_id']) {
                        $operatePrincipal = new OperateEntity([
                            'obj' => $task,
                            'title' => '负责人',
                            'start' => $start,
                            'end' => $currentPrincipalUser->name,
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

        //修改关联资源
        if ($request->has('resource_type')) {
            $resourceableId = hashid_decode($payload['resourceable_id']);
            $resourceType = $payload['resource_type'];
            $taskResource = TaskResource::where('task_id',$task->id)->first();
            if($payload['code'] == 'bloggers'){
                $code = ModuleableType::BLOGGER;
            }elseif($payload['code'] == 'stars'){
                $code = ModuleableType::STAR;
            }elseif($payload['code'] == 'projects'){
                $code = ModuleableType::PROJECT;
            }elseif($payload['code'] == 'clients'){
                $code = ModuleableType::CLIENT;
            }elseif($payload['code'] == 'trails'){
                $code = ModuleableType::TRAIL;
            }else{
                return $this->response->errorInternal('上传类型不正确');
            }
            $resource = [
                'resource_id' => $resourceType,
                'resourceable_id' =>$resourceableId,
                'resourceable_type' =>$code,
                'task_id' =>$task->id,
            ];

            if($taskResource !== null){
                $taskResource->update($resource);
            }else{
                $res = TaskResource::create($resource);

            }

            unset($payload['code']);

        }

        if ($request->has('start_at')) {
            $array['start_at'] = $payload['start_at'];
            $start = $task->start_at;
            $end = $array['start_at'];

            if ($start != $end) {
//                $operateStartAt = new OperateEntity([
//                    'obj' => $task,
//                    'title' => '开始时间',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateStartAt;
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
                //修改日期 如果日期大于当前时间 状态为1正常 反之则状态为4 延期
                $endAt = strtotime($payload['end_at']);
                $currentAt = time();
                if($endAt > $currentAt){
                    $array['status'] = 1;

                }else{
                    $array['status'] = 4;
                }

            } else {
                unset($array['end_at']);
            }
        }

        DB::beginTransaction();
        try {
//            if (count($array) == 0){
//                return $this->response->noContent();
//            }

            if($request->has('resourceable_id')){
                $array['resource_id'] = hashid_decode($payload['resourceable_id']);
                $resourceable_id = hashid_decode($payload['resourceable_id']);

            }

            if($request->has('resource_type')) {
                if ($payload['resource_type'] == 1) {

                    $array['resource_type_name'] = '博主';
                    $resource_name = DB::table('bloggers')->where('bloggers.id', $resourceable_id)->select('nickname as name')->first();
                    $array['resource_name'] = $resource_name->name;

                } elseif ($payload['resource_type'] == 2) {
                    $array['resource_type_name'] = '艺人';
                    $resource_name = DB::table('stars')->where('stars.id', $resourceable_id)->select('name')->first();
                    $array['resource_name'] = $resource_name->name;

                } elseif ($payload['resource_type'] == 3) {

                    $array['resource_type_name'] = '项目';
                    $resource_name = DB::table('projects')->where('projects.id', $resourceable_id)->select('title as name')->first();
                    $array['resource_name'] = $resource_name->name;


                } elseif ($payload['resource_type'] == 4) {
                    $array['resource_type_name'] = '客户';
                    $resource_name = DB::table('clients')->where('clients.id', $resourceable_id)->select('company as name')->first();
                    $array['resource_name'] = $resource_name->name;

                } elseif ($payload['resource_type'] == 5) {
                    $array['resource_type_name'] = '销售线索';
                    $resource_name = DB::table('trails')->where('trails.id', $resourceable_id)->select('title as name')->first();
                    $array['resource_name'] = $resource_name->name;

                }
            }

            $task->update($array);

            unset($array['resource_type_name']);
            unset($array['resource_name']);

            // 操作日志
            event(new OperateLogEvent($arrayOperateLog));
            //event(new TaskDataChangeEvent($oldTask,$task));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();
//        event(new dataChangeEvent($oldTask,$task));
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
                        $model = null;
                        switch ($resource->type) {
                            case ResourceType::BLOGGER:
                                $model = Blogger::findOrFail($resourceableId);
                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::BLOGGER;
                                break;
                            case ResourceType::STAR:

                                $model = Star::findOrFail($resourceableId);
                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::STAR;
//                                //操作日志
//                                $operate = new OperateEntity([
//                                    'obj' => $star,
//                                    'title' => null,
//                                    'start' => null,
//                                    'end' => null,
//                                    'method' => OperateLogMethod::ADD_STAR_TASK,
//                                ]);
//                                event(new OperateLogEvent([
//                                    $operate,
//                                ]));

                                break;
                            case ResourceType::PROJECT:
                                $model = Project::findOrFail($resourceableId);

                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::PROJECT;
                                //操作日志
//                                $operate = new OperateEntity([
//                                    'obj' => $project,
//                                    'title' => null,
//                                    'start' => null,
//                                    'end' => null,
//                                    'method' => OperateLogMethod::ADD_PROJECT_TASK,
//                                ]);
//                                event(new OperateLogEvent([
//                                    $operate,
//                                ]));
                                break;
                            case ResourceType::CLIENT:
                                $model = Client::findOrFail($resourceableId);
                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::CLIENT;
                                //操作日志
//                                $operate = new OperateEntity([
//                                    'obj' => $client,
//                                    'title' => null,
//                                    'start' => null,
//                                    'end' => null,
//                                    'method' => OperateLogMethod::ADD_CLIENT_TASK,
//                                ]);
//                                event(new OperateLogEvent([
//                                    $operate,
//                                ]));

                                break;
                            case ResourceType::TRAIL:
                                $model = Trail::findOrFail($resourceableId);
                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::TRAIL;
                                //操作日志
//                                $operate = new OperateEntity([
//                                    'obj' => $trail,
//                                    'title' => null,
//                                    'start' => null,
//                                    'end' => null,
//                                    'method' => OperateLogMethod::ADD_TRAIL_TASK,
//                                ]);
//                                event(new OperateLogEvent([
//                                    $operate,
//                                ]));
                                break;
                            //TODO
                        }

                        $task_resource = TaskResource::create($array);
                        // 操作日志
                        if ($model != null) {
                            $operate = new OperateEntity([
                                'obj' => $model,
                                'title' => null,
                                'start' => null,
                                'end' => null,
                                'method' => OperateLogMethod::ADD_TASK_RESOURCE,
                            ]);
                            event(new OperateLogEvent([
                                $operate,
                            ]));
                        }
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

            //    添加参与人
            if ($request->has('participant_ids')) {

                $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $task, ModuleUserType::PARTICIPANT);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败!');
        }
        DB::commit();
        //发消息
        $authorization = $request->header()['authorization'][0];
        event(new TaskMessageEvent($task, TaskTriggerPoint::CRATE_TASK, $authorization, $user));
//        DB::beginTransaction();
//        try {
//
//            $user = Auth::guard('api')->user();
//            $title = $user->name . "邀请你参与任务";  //通知消息的标题
//            $subheading = $user->name . "邀请你参与任务";
//            $module = Message::TASK;
//            $link = URL::action("TaskController@show", ["task" => $task->id]);
//            $data = [];
//            $data[] = [
//                "title" => '任务名称', //通知消息中的消息内容标题
//                'value' => $task->title,  //通知消息内容对应的值
//            ];
//            $principal = User::findOrFail($task->principal_id);
//            $data[] = [
//                'title' => '负责人',
//                'value' => $principal->name
//            ];
//
//            $recives = array_column($task->participants()->get()->toArray(),'id');
//            $recives[] = $payload['principal_id'];//负责人
//            $authorization = $request->header()['authorization'][0];
//            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $recives,$task->id);
//
//            DB::commit();
//        }catch (Exception $e){
//            DB::rollBack();
//            Log::error($e);
//        }
        return $this->response->item(Task::find($task->id), new TaskTransformer());
//        return $this->response->created();
    }


    public function taskStore(TaskRequest $request, Task $pTask)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        unset($payload['status']);
        unset($payload['complete_at']);
        unset($payload['stop_at']);
        unset($payload['type_id']);

        $payload['creator_id'] = $user->id;
        if($payload['task_pid'] != 0){
            $payload['task_pid'] = hashid_decode($payload['task_pid']);
        }
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
            if($request->has('resourceable_id')){
                $payload['resource_id'] = hashid_decode($payload['resourceable_id']);
                $resourceable_id = hashid_decode($payload['resourceable_id']);

                if($payload['resource_type'] == 1){
                    $payload['resource_type_name'] = '博主';
                    $resource_name = DB::table('bloggers')->where('bloggers.id',$resourceable_id)->select('nickname as name')->first();
                    $payload['resource_name'] = $resource_name->name;

                }elseif ($payload['resource_type'] == 2){
                    $payload['resource_type_name'] = '艺人';
                    $resource_name = DB::table('stars')->where('stars.id',$resourceable_id)->select('name')->first();
                    $payload['resource_name'] = $resource_name->name;

                }elseif ($payload['resource_type'] == 3){
                    $payload['resource_type_name'] = '项目';
                    $resource_name = DB::table('projects')->where('projects.id',$resourceable_id)->select('title as name')->first();
                    $payload['resource_name'] = $resource_name->name;

                }elseif ($payload['resource_type'] == 4){
                    $payload['resource_type_name'] = '客户';
                    $resource_name = DB::table('clients')->where('clients.id',$resourceable_id)->select('company as name')->first();
                    $payload['resource_name'] = $resource_name->name;

                }elseif ($payload['resource_type'] == 5){
                    $payload['resource_type_name'] = '销售线索';
                    $resource_name = DB::table('trails')->where('trails.id',$resourceable_id)->select('title as name')->first();
                    $payload['resource_name'] = $resource_name->name;

                }
            }

            $task = Task::create($payload);
            unset($payload['type_name']);
            unset($payload['resource_name']);
            unset($payload['task_pid']);
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
                        $model = null;

                        switch ($resource->type) {
                            case ResourceType::BLOGGER:
                                $model = Blogger::findOrFail($resourceableId);
                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::BLOGGER;
                                break;
                            case ResourceType::STAR:

                                $model = Star::findOrFail($resourceableId);

                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::STAR;

//                                //操作日志
//                                $operate = new OperateEntity([
//                                    'obj' => $star,
//                                    'title' => null,
//                                    'start' => null,
//                                    'end' => null,
//                                    'method' => OperateLogMethod::ADD_STAR_TASK,
//                                ]);
//                                event(new OperateLogEvent([
//                                    $operate,
//                                ]));

                                break;
                            case ResourceType::PROJECT:
                                $model = Project::findOrFail($resourceableId);

                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::PROJECT;
                                //操作日志
//                                $operate = new OperateEntity([
//                                    'obj' => $project,
//                                    'title' => null,
//                                    'start' => null,
//                                    'end' => null,
//                                    'method' => OperateLogMethod::ADD_PROJECT_TASK,
//                                ]);
//                                event(new OperateLogEvent([
//                                    $operate,
//                                ]));
                                break;
                            case ResourceType::CLIENT:
                                $model = Client::findOrFail($resourceableId);
                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::CLIENT;
                                //操作日志
//                                $operate = new OperateEntity([
//                                    'obj' => $client,
//                                    'title' => null,
//                                    'start' => null,
//                                    'end' => null,
//                                    'method' => OperateLogMethod::ADD_CLIENT_TASK,
//                                ]);
//                                event(new OperateLogEvent([
//                                    $operate,
//                                ]));

                                break;
                            case ResourceType::TRAIL:
                                $model = Trail::findOrFail($resourceableId);
                                $array['resourceable_id'] = $model->id;
                                $array['resourceable_type'] = ModuleableType::TRAIL;
                                //操作日志
//                                $operate = new OperateEntity([
//                                    'obj' => $trail,
//                                    'title' => null,
//                                    'start' => null,
//                                    'end' => null,
//                                    'method' => OperateLogMethod::ADD_TRAIL_TASK,
//                                ]);
//                                event(new OperateLogEvent([
//                                    $operate,
//                                ]));
                                break;
                            //TODO
                        }

                        $task_resource = TaskResource::create($array);

                        // 操作日志
                        if ($model != null){
                            $operate = new OperateEntity([
                                'obj' => $model,
                                'title' => null,
                                'start' => null,
                                'end' => null,
                                'method' => OperateLogMethod::ADD_TASK_RESOURCE,
                            ]);
                            event(new OperateLogEvent([
                                $operate,
                            ]));
                        }
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

            //    添加参与人
            if ($request->has('participant_ids')) {

                $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $task, ModuleUserType::PARTICIPANT);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败!');
        }
        DB::commit();
        //发消息
        $authorization = $request->header()['authorization'][0];
        event(new TaskMessageEvent($task,TaskTriggerPoint::CRATE_TASK,$authorization,$user));

//        DB::beginTransaction();
//        try {
//
//            $user = Auth::guard('api')->user();
//            $title = $user->name . "邀请你参与任务";  //通知消息的标题
//            $subheading = $user->name . "邀请你参与任务";
//            $module = Message::TASK;
//            $link = URL::action("TaskController@show", ["task" => $task->id]);
//            $data = [];
//            $data[] = [
//                "title" => '任务名称', //通知消息中的消息内容标题
//                'value' => $task->title,  //通知消息内容对应的值
//            ];
//            $principal = User::findOrFail($task->principal_id);
//            $data[] = [
//                'title' => '负责人',
//                'value' => $principal->name
//            ];
//
//            $recives = array_column($task->participants()->get()->toArray(),'id');
//            $recives[] = $payload['principal_id'];//负责人
//            $authorization = $request->header()['authorization'][0];
//            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $recives,$task->id);
//
//            DB::commit();
//        }catch (Exception $e){
//            DB::rollBack();
//            Log::error($e);
//        }
        return $this->response->item(Task::find($task->id), new TaskTransformer());
//        return $this->response->created();
    }

    public function filter(FilterTaskRequest $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $tasks = Task::where(function ($query) use ($request, $payload) {

            if ($request->has('keyword'))
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('type_id'))
                $query->where('type_id', hashid_decode($payload['type_id']));
            if ($request->has('status'))
                $query->where('status', $payload['status']);

        })->searchData()->orderBy('updated_at', 'desc')->paginate($pageSize);//created_at

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

    public function secret(Request $request, Task $task)
    {

        $payload = $request->all();
        $privacy = isset($payload['privacy']) && $payload['privacy'] == 1 ? $payload['privacy'] : 0;
        DB::beginTransaction();
        try {
            if($privacy ==1){
                $id = $task->creator_id;
                $info = DB::select("call getprincipal($id)");
                if($info){
                    $data = json_decode(json_encode($info), true);
                    $adjId = array_unique(array_column($data, 'user_id'));
                    $adjIdStr = implode(",", $adjId);
                }else{
                    $adjIdStr = 0;
                }

            }else{
                $adjIdStr = 0;
            }
            //修改任务私密状态
            $array = [
                'privacy' => $privacy,
                'adj_id'=>$adjIdStr
            ];

            $task->update($array);

        $operate = new OperateEntity([
            'obj' => $task,
            'title' => $task->privacy == 1 ? "将任务转私密":"将任务转公开",
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::TASK_TO_SECRET,
            'field_name'    =>  'privacy',
            'field_title'   =>  '隐私'
            ]);
        event(new OperateLogEvent([
            $operate,
        ]));

        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();
    }

    public function dashboard(Request $request, Department $department)
    {
        $days = $request->get('days', 7);
        $departmentId = $department->id;
        $departmentArr = Common::getChildDepartment($departmentId);
        $userIds = DepartmentUser::whereIn('department_id', $departmentArr)->pluck('user_id');

        $tasks = Task::select('tasks.id as id', DB::raw('GREATEST(tasks.created_at, COALESCE(MAX(operate_logs.created_at), 0)) as t'), 'tasks.title')
            ->whereIn('tasks.principal_id', $userIds)
            ->leftJoin('operate_logs', function ($join) {
                $join->on('tasks.id', '=', 'operate_logs.logable_id')
                    ->where('operate_logs.logable_type', ModuleableType::TASK)
                    ->where('operate_logs.method', OperateLogMethod::FOLLOW_UP);
            })->groupBy('tasks.id')
            ->orderBy('t', 'desc')
            ->take(5)->get();

        $result = $this->response->collection($tasks, new DashboardModelTransformer());

        $count = Task::whereIn('principal_id', $userIds)->count('id');
        $delayCount = Task::whereIn('principal_id', $userIds)->where('status', TaskStatus::DELAY)->count('id');

        $timePoint = Carbon::today('PRC')->subDays($days);
        $newTasks = Task::whereIn('principal_id', $userIds)->where('created_at', '>', $timePoint)->count('id');

        $completed = Task::whereIn('principal_id', $userIds)->where('status', TaskStatus::COMPLETE)->count('id');
        $progressing = Task::whereIn('principal_id', $userIds)->where('status', TaskStatus::NORMAL)->count('id');

        $taskInfoArr = [
            'total' => $count,
            'completed' => $completed,
            'progressing' => $progressing,
            'delayed' => $delayCount,
            'latest' => $newTasks,
        ];

        $result->addMeta('count', $taskInfoArr);
        return $result;
    }
}
