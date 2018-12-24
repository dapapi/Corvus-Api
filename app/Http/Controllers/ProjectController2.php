<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\AddRelateProjectRequest;
use App\Http\Requests\Project\EditProjectRequest;
use App\Http\Requests\Project\SearchProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Transformers\ProjectTransformer;
use App\Http\Transformers\TemplateFieldTransformer;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\FieldValue;
use App\Models\Message;
use App\Models\MessageState;
use App\Models\ModuleUser;
use App\Models\Project;
use App\Models\ProjectRelate;
use App\Models\Resource;
use App\Models\Star;
use App\Models\Task;
use App\Models\TemplateField;
use App\Models\Trail;
use App\Models\TrailStar;
use App\Models\User;
use App\ModuleableType;
use App\ModuleUserType;
use App\Repositories\MessageRepository;
use App\Repositories\ModuleUserRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\ScopeRepository;
use Dingo\Api\Facade\Route;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;

class ProjectController extends Controller
{
    protected $moduleUserRepository;

    public function __construct(ModuleUserRepository $moduleUserRepository)
    {
        $this->moduleUserRepository = $moduleUserRepository;
    }

    // 项目列表
    public function index(Request $request)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));
        //可查询的数据范围
//        $arrUserId = (new ScopeRepository())->getDataViewUsers();
//        if($arrUserId === null){
//            return $this->response->errorInternal("没有查看数据的权限");
//        }
        $projects = Project::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword'))
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');

            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }

            if ($request->has('status'))
                $query->where('status', $payload['status']);
//            //限制查询数据范围
//            if(count($arrUserId) > 0){
//                $query->whereIn('creator_id',$arrUserId)
//                ->orWhereIn('principal_id',$arrUserId);
//            }
        })->orderBy('created_at', 'desc')->paginate($pageSize);
        return $this->response->paginator($projects, new ProjectTransformer());
    }

    public function all(Request $request)
    {
        $isAll = $request->get('all', false);
        //可查询的数据范围
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId === null){
            return $this->response->errorInternal("没有查看数据的权限");
        }
        $projects = Project::orderBy('created_at', 'desc')->where(function ($query)use ($arrUserId){
            //限制查询数据范围
            if(count($arrUserId) > 0){
                $query->whereIn('creator_id',$arrUserId)
                    ->orWhereIn('principal_id',$arrUserId);
            }
        })->get();
        return $this->response->collection($projects, new ProjectTransformer($isAll));
    }

    public function myAll(Request $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        if ($request->has('page_size')) {
            $pageSize = $payload['page_size'];
        } else {
            $pageSize = config('api.page_size');
        }

        $type = $request->get('type', 0);

        $status = $request->get('status', 0);

        $projects = DB::table('projects')->select('projects.*');
        switch ($status) {
            case Project::STATUS_NORMAL:
                $projects->where('status', Project::STATUS_NORMAL);
                break;
            case Project::STATUS_COMPLETE:
                $projects->where('status', Project::STATUS_COMPLETE);
                break;
            case Project::STATUS_FROZEN:
                $projects->where('status', Project::STATUS_FROZEN);
                break;
            default:
                break;
        }

        $projects->where(function ($query) use ($userId) {
            $query->where('creator_id', $userId)->orWhere('principal_id', $userId);
        });

        $query = DB::table('projects')->select('projects.*')->join('module_users', function ($join) use ($userId) {
            $join->on('module_users.moduleable_id', '=', 'projects.id')
                ->where('module_users.moduleable_type', ModuleableType::PROJECT)
                ->where('module_users.user_id', $userId);
        });

        switch ($status) {
            case Project::STATUS_NORMAL:
                $query->where('status', Project::STATUS_NORMAL);
                break;
            case Project::STATUS_COMPLETE:
                $query->where('status', Project::STATUS_COMPLETE);
                break;
            case Project::STATUS_FROZEN:
                $query->where('status', Project::STATUS_FROZEN);
                break;
            default:
                break;
        }

        $query->union($projects);

        $querySql = $query->toSql();
        $result = Project::rightJoin(DB::raw("($querySql) as a"), function ($join) {
            $join->on('projects.id', '=', 'a.id');
        })->mergeBindings($query)
            ->orderBy('a.created_at', 'desc')
            ->paginate($pageSize);
        return $this->response->paginator($result, new ProjectTransformer());
    }

    public function my(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', 0);
        $type = $request->get('type', 1);

        $query = Project::select('projects.*');

        switch ($type) {
            case 2://我负责
                $query->where('principal_id', $user->id);
                break;
            case 3://我参与
                $query = $user->participantProjects();
                break;
            case 1://我创建
            default:
                $query->where('creator_id', $user->id);
                break;
        }
        switch ($status) {
            case Project::STATUS_NORMAL://进行中
                $query->where('status', Project::STATUS_NORMAL);
                break;
            case Project::STATUS_COMPLETE://完成
                $query->where('status', Project::STATUS_COMPLETE);
                break;
            case Project::STATUS_FROZEN://终止
                $query->where('status', Project::STATUS_FROZEN);
                break;
            default:
                break;
        }
        $projects = $query->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($projects, new ProjectTransformer());
    }

    public function store(Request $request)
    {
        // todo 可能涉及筛选可选线索
        $payload = $request->all();

        if ($payload['type'] != 5 && !$request->has('fields')) {
            return $this->response->errorBadRequest('缺少参数');
        } elseif ($request->has('fields')) {
            foreach ($payload['fields'] as $key => $val) {
                $fieldId = hashid_decode((int)$key);
                $field = TemplateField::where('module_type', $payload['type'])->find($fieldId);
                if (!$field) {
                    return $this->response->errorBadRequest('字段与项目类型匹配错误');
                }
            }
            $payload['trail_id'] = hashid_decode($payload['trail']['id']);
            unset($payload['trail']['id']);
        }

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        $payload['principal_id'] = hashid_decode($payload['principal_id']);

        DB::beginTransaction();
        try {
            $project = Project::create($payload);
            $projectId = $project->id;

            if ($payload['type'] != 5) {
                foreach ($payload['fields'] as $key => $val) {
                    FieldValue::create([
                        'field_id' => hashid_decode((int)$key),
                        'project_id' => $projectId,
                        'value' => $val,
                    ]);
                }

                // todo 优化，这部分操作应该有对应仓库
                // todo 操作日志的时候在对应的trail也要记录
                $trail = Trail::find($payload['trail_id']);
                foreach ($payload['trail'] as $key => $val) {
                    if ($key == 'lock') {
                        $trail->lock_status = $val;
                        continue;
                    }

                    if ($key == 'fee') {
                        $trail->fee = $val;
                        continue;
                    }

                    if ($key == 'expectations') {
                        if ($trail->type == Trail::TYPE_PAPI) {
                            $starableType = ModuleableType::BLOGGER;
                        } else {
                            $starableType = ModuleableType::STAR;
                        }
                        TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::EXPECTATION)->delete();
                        foreach ($val as $expectation) {
                            $starId = hashid_decode($expectation);
                            if ($starableType == ModuleableType::BLOGGER) {
                                if (Blogger::find($starId)) {
                                    TrailStar::create([
                                        'trail_id' => $trail->id,
                                        'starable_id' => $starId,
                                        'starable_type' => $starableType,
                                        'type' => TrailStar::EXPECTATION,
                                    ]);
                                }
                            } else {
                                if (Star::find($starId)) {
                                    TrailStar::create([
                                        'trail_id' => $trail->id,
                                        'starable_id' => $starId,
                                        'starable_type' => $starableType,
                                        'type' => TrailStar::EXPECTATION,
                                    ]);
                                }
                            }
                        }
                        continue;
                    }

                    if ($key == 'recommendations') {
                        if ($trail->type == Trail::TYPE_PAPI) {
                            $starableType = ModuleableType::BLOGGER;
                        } else {
                            $starableType = ModuleableType::STAR;
                        }
                        TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::RECOMMENDATION)->delete();
                        foreach ($val as $recommendation) {
                            $starId = hashid_decode($recommendation);

                            if ($starableType == ModuleableType::BLOGGER) {
                                if (Blogger::find($starId))
                                    TrailStar::create([
                                        'trail_id' => $trail->id,
                                        'starable_id' => $starId,
                                        'starable_type' => $starableType,
                                        'type' => TrailStar::RECOMMENDATION,
                                    ]);
                            } else {
                                if (Star::find($starId))
                                    TrailStar::create([
                                        'trail_id' => $trail->id,
                                        'starable_id' => $starId,
                                        'starable_type' => $starableType,
                                        'type' => TrailStar::RECOMMENDATION,
                                    ]);
                            }
                        }

                    }
                }
                $trail->update($payload['trail']);
            }

            if ($request->has('participant_ids')) {
                foreach ($payload['participant_ids'] as &$id) {
                    $id = hashid_decode($id);
                }
                unset($id);
                $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $project, ModuleUserType::PARTICIPANT);
            }

        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->item($project, new ProjectTransformer());

    }

    public function edit(EditProjectRequest $request, Project $project)
    {
        //获取项目的参与者
        $res = $project->participants()->get();
        //验证权限
        $power = (new ScopeRepository())->checkMangePower($project->creator_id,$project->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有编辑该项目的权限");
        }
        $payload = $request->all();

        if ($request->has('principal_id'))
            $payload['principal_id'] = hashid_decode($payload['principal_id']);

        if (!$request->has('type'))
            $payload['type'] = $project->type;

        if ($request->has('fields')) {
            foreach ($payload['fields'] as $key => $val) {
                $fieldId = hashid_decode((int)$key);
                $field = TemplateField::where('module_type', $payload['type'])->find($fieldId);
                if (!$field) {
                    return $this->response->errorBadRequest('字段与项目类型匹配错误');
                }
            }
        }

        if (!$request->has('participant_ids') || !is_array($payload['participant_ids']))
            $payload['participant_ids'] = [];

        if (!$request->has('participant_del_ids') || !is_array($payload['participant_del_ids']))
            $payload['participant_del_ids'] = [];

        DB::beginTransaction();
        try {
            $project->update($payload);
            $projectId = $project->id;

            $trail = $project->trail;

            $this->moduleUserRepository->addModuleUser($payload['participant_ids'], $payload['participant_del_ids'], $project, ModuleUserType::PARTICIPANT);

            if ($request->has('fields')) {
                foreach ($payload['fields'] as $key => $val) {
                    $fieldId = hashid_decode((int)$key);
                    $fieldValue = FieldValue::where('field_id', $fieldId)->where('project_id', $projectId)->first();
                    if ($fieldValue) {
                        $fieldValue->value = $val;
                        $fieldValue->save();
                    } else {
                        FieldValue::create([
                            'field_id' => $fieldId,
                            'project_id' => $projectId,
                            'value' => $val,
                        ]);
                    }
                }

            }

            if ($request->has('trail')) {


                foreach ($payload['trail'] as $key => $val) {

                    if ($key == 'lock') {
                        $trail->lock_status = $val;
                        continue;
                    }

                    if ($key == 'fee') {
                        $trail->fee = $val;
                        continue;
                    }

                    if ($key == 'expectations') {
                        if ($trail->type == Trail::TYPE_PAPI) {
                            $starableType = ModuleableType::BLOGGER;
                        } else {
                            $starableType = ModuleableType::STAR;
                        }
                        TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::EXPECTATION)->delete();
                        foreach ($val as $expectation) {
                            $starId = hashid_decode($expectation);
                            if ($starableType == ModuleableType::BLOGGER) {
                                if (Blogger::find($starId)) {
                                    TrailStar::create([
                                        'trail_id' => $trail->id,
                                        'starable_id' => $starId,
                                        'starable_type' => $starableType,
                                        'type' => TrailStar::EXPECTATION,
                                    ]);
                                }
                            } else {
                                if (Star::find($starId)) {
                                    TrailStar::create([
                                        'trail_id' => $trail->id,
                                        'starable_id' => $starId,
                                        'starable_type' => $starableType,
                                        'type' => TrailStar::EXPECTATION,
                                    ]);
                                }
                            }
                        }
                        continue;
                    }

                    if ($key == 'recommendations') {
                        if ($trail->type == Trail::TYPE_PAPI) {
                            $starableType = ModuleableType::BLOGGER;
                        } else {
                            $starableType = ModuleableType::STAR;
                        }
                        TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::RECOMMENDATION)->delete();
                        foreach ($val as $recommendation) {
                            $starId = hashid_decode($recommendation);

                            if ($starableType == ModuleableType::BLOGGER) {
                                if (Blogger::find($starId))
                                    TrailStar::create([
                                        'trail_id' => $trail->id,
                                        'starable_id' => $starId,
                                        'starable_type' => $starableType,
                                        'type' => TrailStar::RECOMMENDATION,
                                    ]);
                            } else {
                                if (Star::find($starId))
                                    TrailStar::create([
                                        'trail_id' => $trail->id,
                                        'starable_id' => $starId,
                                        'starable_type' => $starableType,
                                        'type' => TrailStar::RECOMMENDATION,
                                    ]);
                            }
                        }

                    }
                    $trail->update($payload['trail']);
                }
            }

        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('修改失败,' . $exception->getMessage());
        }
        DB::commit();
        DB::beginTransaction();
        try{
            $user = Auth::guard('api')->user();
            $title = $user->name."将你加入了项目";  //通知消息的标题
            $subheading = "副标题";
            $module = Message::PROJECT;
            $link = URL::action("ProjectController@detail",["project"=>$project->id]);
            $data = [];
            $data[] = [
                "title" =>  '项目名称', //通知消息中的消息内容标题
                'value' =>  $project->title,  //通知消息内容对应的值
            ];
            $principal = User::findOrFail($project->principal_id);
            $data[] = [
                'title' =>  '项目负责人',
                'value' =>  $principal->name
            ];
            $participant_ids = isset($payload['participant_ids']) ? $payload['participant_ids'] : null;
            $authorization = $request->header()['authorization'][0];
            (new MessageRepository())->addMessage($user,$authorization,$title,$subheading,$module,$link,$data,$participant_ids);
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
        }

        return $this->response->accepted();
    }

    public function detail(Request $request, $project)
    {
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId == null || (count($arrUserId)!=0 && !in_array($project->creator_id,$arrUserId) && !in_array($project->principal_id,$arrUserId))){
            return $this->response->errorInternal("你没有查看该项目的权限");
        }
        $type = $project->type;
        $result = $this->response->item($project, new ProjectTransformer());

        $data = TemplateField::where('module_type', $type)->get();
        $resource = new Fractal\Resource\Collection($data, new TemplateFieldTransformer($project->id));
        $manager = new Manager();
        $manager->setSerializer(new DataArraySerializer());

        $result->addMeta('fields', $manager->createData($resource)->toArray());

        return $result;
    }

    public function delete(Request $request, Project $project)
    {
        //获取项目的参与者
        $res = $project->participants()->get();
        //验证权限
        $power = (new ScopeRepository())->checkMangePower($project->creator_id,$project->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有删除该项目的权限");
        }
        $arrUserId = (new ScopeRepository())->getUserIds();
        if($arrUserId == null || (!in_array($project->creator_id,$arrUserId) && !in_array($project->principal_id,$arrUserId))){
            return $this->response->errorInternal("你没有删除该项目状态的权限");
        }
        try {
            $project->status = Project::STATUS_DEL;
            $project->save();
            $project->delete();
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('删除失败');
        }

        return $this->response->noContent();
    }

    public function recover(Request $request, Project $project)
    {
        $project->restore();
        $project->status = Project::STATUS_NORMAL;
        $project->save();

        return $this->response->item($project, new ProjectTransformer());
    }

    public function changeStatus(Request $request, Project $project)
    {
        //获取项目的参与者
        $res = $project->participants()->get();
        //验证权限
        $power = (new ScopeRepository())->checkMangePower($project->creator_id,$project->principal_id,array_column($res->toArray(),'id'));
        if(!$power){
            return $this->response->errorInternal("你没有改变该项目状态的权限");
        }
        if (!$request->has('status'))
            return $this->response->errorBadRequest('参数错误');

        $status = $request->get('status');
        switch ($status) {
            case Project::STATUS_COMPLETE:
                $project->complete_at = now();
                $project->status = $status;
                break;
            case Project::STATUS_FROZEN:
                $project->stop_at = now();
                $project->status = $status;
                break;
            case Project::STATUS_NORMAL:
                $project->stop_at = null;
                $project->complete_at = null;
                $project->status = $status;
                break;
            default:
                break;
        }

        $project->save();

        return $this->response->item($project, new ProjectTransformer());
    }

    public function filter(Request $request)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));
        //可查询的数据范围
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId === null){
            return $this->response->errorInternal("没有查看数据的权限");
        }
        $projects = Project::where(function ($query) use ($request, $payload,$arrUserId) {
            if ($request->has('keyword'))
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');

            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }

            if ($request->has('status'))
                $query->where('status', $payload['status']);
            //限制查询数据范围
            if(count($arrUserId) > 0){
                $query->whereIn('creator_id',$arrUserId)
                    ->orWhereIn('principal_id',$arrUserId);
            }
        })->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($projects, new ProjectTransformer());

    }

    /**
     * 获取明星下的项目
     * @param Request $request
     * @return mixed
     */
    public function getStarProject(Request $request)
    {
        $star_id = $request->get('star_id', null);
        $star_id = hashid_decode($star_id);
        $result = ProjectRepository::getProjectBySatrId($star_id);

        //todo 这里的返回值status没有返回数字，返回的是中文所以用不了transfromer
//        return $this->response->collection($result, new ProjectTransformer());
        return $result;
    }

    public function getClientProject(Request $request, Client $client)
    {
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId == null || (count($arrUserId)!=0 && !in_array($client->creator_id,$arrUserId) && !in_array($client->principal_id,$arrUserId))){
            return $this->response->errorInternal("你没有查看该客户的权限");
        }
        $pageSize = $request->get('page_size', config('app.page_size'));

        $projects = Project::select('projects.*')->join('trails', function ($join) {
            $join->on('projects.trail_id', '=', 'trails.id');
        })->where('trails.client_id', '=', $client->id)
            ->paginate($pageSize);

        return $this->response->paginator($projects, new ProjectTransformer());
    }

    /**
     * 项目关联项目 关联任务
     * @param AddRelateProjectRequest $request
     * @param Project $project
     * @return \Dingo\Api\Http\Response|void
     */
    public function addRelates(AddRelateProjectRequest $request, Project $project)
    {
        DB::beginTransaction();
        try {

            if ($request->has('tasks')) {
                ProjectRelate::where('project_id', $project->id)->where('moduleable_type', ModuleableType::TASK)->delete();
                $tasks = $request->get('tasks');
                foreach ($tasks as $value) {
                    $id = hashid_decode($value);
                    if (Task::find($id))
                        ProjectRelate::create([
                            'project_id' => $project->id,
                            'moduleable_id' => $id,
                            'moduleable_type' => ModuleableType::TASK,
                        ]);
                }
            }

            if ($request->has('projects')) {
                ProjectRelate::where('project_id', $project->id)->where('moduleable_type', ModuleableType::PROJECT)->delete();
                $projects = $request->get('projects');
                foreach ($projects as $value) {
                    $id = hashid_decode($value);
                    if (Project::find($id))
                        ProjectRelate::create([
                            'project_id' => $project->id,
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
