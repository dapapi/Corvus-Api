<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Events\ProjectDataChangeEvent;


use App\Models\Department;
use App\Models\DepartmentUser;
use App\Repositories\FilterReportRepository;

use App\Events\TrailDataChangeEvent;
use App\Exports\ProjectsExport;
use App\Helper\Common;
use App\Http\Requests\Filter\FilterRequest;
use App\Http\Requests\Project\AddRelateProjectRequest;
use App\Http\Requests\Project\EditEeturnedMoneyRequest;
use App\Http\Requests\Project\EditProjectRequest;
use App\Http\Requests\Project\ReturnedMoneyRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Transformers\DashboardModelTransformer;
use App\Http\Transformers\ProjectCourseTransformer;
use App\Http\Transformers\ProjectReturnedMoneyShowTransformer;
use App\Http\Transformers\ProjectReturnedMoneyTransformer;
use App\Http\Transformers\ProjectReturnedMoneyTypeTransformer;
use App\Http\Transformers\ProjectTransformer;
use App\Http\Transformers\simpleProjectTransformer;
use App\Http\Transformers\StarProjectTransformer;
use App\Http\Transformers\TemplateFieldTransformer;
use App\Models\Blogger;
use App\Models\FilterJoin;
use App\Models\Client;



use App\Models\FieldHistorie;
use App\Models\FieldValue;
use App\Models\Message;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\ProjectBill;
use App\Models\ProjectHistorie;
use App\Models\ProjectRelate;
use App\Models\ProjectReturnedMoney;
use App\Models\ProjectReturnedMoneyType;
use App\Models\ProjectStatusLogs;
use App\Models\Star;
use App\Models\Task;
use App\Models\TemplateField;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\PrivacyType;

use App\Repositories\MessageRepository;
use App\Repositories\ModuleUserRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\ScopeRepository;
use App\Repositories\TrailStarRepository;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
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
        $user = Auth::guard('api')->user();
        $project_type = $request->get('project_type',null);
        $query =  Project::where(function ($query) use ($request, $payload,$user,$project_type) {
            if ($request->has('keyword'))
                $query->where('projects.title', 'LIKE', '%' . $payload['keyword'] . '%');

            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }

            if ($request->has('project_type') && $project_type <> '3,4' ){
                $query->where('projects.type',$project_type);

            }
            if($request->has('project_type') && $project_type == '3,4'){
                $query->whereIn('projects.type',[$project_type]);
            }
            if ($request->has('status'))#项目状态
                $query->where('status', $payload['status']);
        });
        if ($request->has('my')){
            switch ($payload['my']){
                case 'my_principal'://我负责
                    $query->where('principal_id', $user->id);
                    break;
                case 'my_participant'://我参与
                    $query->leftJoin("module_users as mu2",function ($join){
                        $join->on("mu2.moduleable_id","projects.id")
                            ->where('mu2.moduleable_type',ModuleableType::PROJECT);
                    })->where('mu2.user_id',$user->id);
                    break;
                case 'my_create'://我创建
                    $query->where('projects.creator_id', $user->id);
                    break;

            }
        }
            //$projects = $query->orderBy('projects.created_at', 'desc')->paginate($pageSize);
        $projects = $query->searchData()
        ->leftJoin('operate_logs',function($join){
            $join->on('projects.id','operate_logs.logable_id')
                ->where('logable_type',ModuleableType::PROJECT)
                ->where('operate_logs.method','4');
        })->groupBy('projects.id')
        ->orderBy('up_time', 'desc')->orderBy('projects.created_at', 'desc')->select(['projects.id','creator_id','project_number','trail_id','title','projects.type','privacy','projects.status',
            'principal_id','projected_expenditure','priority','start_at','end_at','projects.created_at','projects.updated_at', DB::raw("max(operate_logs.updated_at) as up_time"),'desc'])
//        $sql_with_bindings = str_replace_array('?', $projects->getBindings(), $projects->toSql());
////
//        dd($sql_with_bindings);
        ->paginate($pageSize);
        //  修改项目排序   按跟进时间  和 创建时间排序
        return $this->response->paginator($projects, new ProjectTransformer());
    }

    public function getProjectRelated(Request $request){

        $projects =  Project::searchData()->select('id','title')->get();
        $data = array();
        $data['data'] = $projects;
        foreach ($data['data'] as $key => &$value) {
            $value['id'] = hashid_encode($value['id']);
        }
        return $data;


    }

    public function all(Request $request)
    {
        $isAll = $request->get('all', false);
        $status = $request->get('status', null);
        if (is_null($status))
            $projects = Project::orderBy('created_at', 'desc')->searchData()->get();
        else
            $projects = Project::orderBy('created_at', 'desc')->where('status', $status)->searchData()->get();

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
        $project_type = $request->get('project_type',null);
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
        if ($request->has('project_type') && $project_type <> '3,4' ){
            $query->where('type',$project_type);

        }
        if($request->has('project_type') && $project_type == '3,4'){
            $query->whereIn('type',[$project_type]);
        }
        $projects = $query->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($projects, new ProjectTransformer());
    }

    public function store(StoreProjectRequest $request)
    {
        // todo 可能涉及筛选可选线索
        $payload = $request->all();
        $arrayOperateLog = [];
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
            if (is_array($payload['trail']) && array_key_exists('id', $payload['trail'])) {
                $payload['trail_id'] = hashid_decode($payload['trail']['id']);
                unset($payload['trail']['id']);
            }
            $payload['project_number'] = Project::getProjectNumber();
        }

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        $payload['principal_id'] = hashid_decode($payload['principal_id']);

        DB::beginTransaction();

        try {
            $project = Project::create($payload);
            $projectId = $project->id;


            if ($payload['type'] != 5) {
                $projectHistorie = ProjectHistorie::create($payload);
                $approvalForm = new ApprovalFormController();
                $approvalForm->projectStore($request,$payload['type'], $payload['notice'], $payload['project_number']);
                foreach ($payload['fields'] as $key => $val) {
                    FieldValue::create([
                        'field_id' => hashid_decode((int)$key),
                        'project_id' => $projectId,
                        'value' => $val,
                    ]);
                    FieldHistorie::create([
                        'field_id' => hashid_decode((int)$key),
                        'project_id' => $projectHistorie->id,
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
                        $repository = new TrailStarRepository();
                        //获取现在关联的艺人和博主
                        $start = $repository->getStarListByTrailId($trail->id,TrailStar::EXPECTATION);
                        $repository->deleteTrailStar($trail->id,TrailStar::EXPECTATION);
                        $repository->store($trail,$payload['trail']['expectations'],TrailStar::EXPECTATION);
                        //获取更新之后的艺人和博主列表
                        $end = $repository->getStarListByTrailId($trail->id,TrailStar::EXPECTATION);
//                        $start = null;
//                        $end = null;
//                        if ($trail->type == Trail::TYPE_PAPI) {
//                            $starableType = ModuleableType::BLOGGER;
//                            //获取当前的博主
//                            $blogger_list = $trail->bloggerExpectations()->get()->toArray();
//                            if (count($blogger_list) != 0) {
//                                $bloggers = array_column($blogger_list, 'nickname');
//                                $start = implode(",", $bloggers);
//                            }
//                        } else {
//                            $starableType = ModuleableType::STAR;
//                            //获取当前的艺人
//                            $star_list = $trail->expectations()->get()->toArray();
//                            if (count($star_list) != 0) {
//                                $stars = array_column($star_list, 'name');
//                                $start = implode(",", $stars);
//                            }
//                        }
//                        //删除
//                        TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::EXPECTATION)->delete();
//
//                        foreach ($val as $expectation) {
//                            $starId = hashid_decode($expectation);
//                            if ($starableType == ModuleableType::BLOGGER) {
//                                $blogger = Blogger::find($starId);
//                                if ($blogger) {
//                                    $end .= "," . $blogger->nickname;
//                                    TrailStar::create([
//                                        'trail_id' => $trail->id,
//                                        'starable_id' => $starId,
//                                        'starable_type' => $starableType,
//                                        'type' => TrailStar::EXPECTATION,
//                                    ]);
//                                }
//                            } else {
//                                $star = Star::find($starId);
//                                if ($star) {
//                                    $end .= "," . $star->name;
//                                    TrailStar::create([
//                                        'trail_id' => $trail->id,
//                                        'starable_id' => $starId,
//                                        'starable_type' => $starableType,
//                                        'type' => TrailStar::EXPECTATION,
//                                    ]);
//                                }
//                            }
//                        }
//                        if ($starableType == ModuleableType::BLOGGER) {
//                            $title = "关联目标博主";
//                        } else {
//                            $title = "关联目标艺人";
//                        }
                        $title = "关联目标艺人";
                        if (!empty($start) || !empty($end)) {
                            $operateName = new OperateEntity([
                                'obj' => $trail,
                                'title' => $title,
                                'start' => $start,
                                'end' => trim($end, ","),
                                'method' => OperateLogMethod::UPDATE,
                            ]);
                            $arrayOperateLog[] = $operateName;
                        }

                        continue;
                    }

                    if ($key == 'recommendations') {
                        $repository = new TrailStarRepository();
                        //获取现在关联的艺人和博主
                        $start = $repository->getStarListByTrailId($trail->id,TrailStar::RECOMMENDATION);
                        $repository->deleteTrailStar($trail->id,TrailStar::RECOMMENDATION);
                        $repository->store($trail,$payload['trail']['recommendations'],TrailStar::RECOMMENDATION);
                        //获取更新之后的艺人和博主列表
                        $end = $repository->getStarListByTrailId($trail->id,TrailStar::RECOMMENDATION);
//                        $start = null;
//                        $end = null;
//                        if ($trail->type == Trail::TYPE_PAPI) {
//                            $starableType = ModuleableType::BLOGGER;
//                            //当前关联的博主
//                            $blogger_list = $trail->bloggerRecommendations()->get()->toArray();
//                            $bloggers = array_column($blogger_list, 'nickname');
//                            $start = implode(",", $bloggers);
//                        } else {
//                            $starableType = ModuleableType::STAR;
//                            $star_list = $trail->recommendations()->get()->toArray();
//                            $stars = array_column($star_list, 'name');
//                            $start = implode(",", $stars);
//                        }
//                        //删除
//                        TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::RECOMMENDATION)->delete();
//                        foreach ($val as $recommendation) {
//                            $starId = hashid_decode($recommendation);
//
//                            if ($starableType == ModuleableType::BLOGGER) {
//                                $blogger = Blogger::find($starId);
//                                if ($blogger) {
//                                    $end .= $blogger->nickname;
//                                    TrailStar::create([
//                                        'trail_id' => $trail->id,
//                                        'starable_id' => $starId,
//                                        'starable_type' => $starableType,
//                                        'type' => TrailStar::RECOMMENDATION,
//                                    ]);
//                                }
//                            } else {
//                                $star = Star::find($starId);
//                                if ($star) {
//                                    $end .= $star->name;
//                                    TrailStar::create([
//                                        'trail_id' => $trail->id,
//                                        'starable_id' => $starId,
//                                        'starable_type' => $starableType,
//                                        'type' => TrailStar::RECOMMENDATION,
//                                    ]);
//                                }
//
//                            }
//                        }
//                        if ($starableType == ModuleableType::BLOGGER) {
//                            $title = "关联推荐博主";
//                        } else {
//                            $title = "关联推荐艺人";
//                        }
                        $title = "关联推荐艺人";
                        if (!empty($start) || !empty($end)) {
                            $operateName = new OperateEntity([
                                'obj' => $trail,
                                'title' => $title,
                                'start' => $start,
                                'end' => trim($end, ","),
                                'method' => OperateLogMethod::UPDATE,
                            ]);
                            $arrayOperateLog[] = $operateName;
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
            // 操作日志
            $operateName = new OperateEntity([
                'obj' => $project,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            $arrayOperateLog[] = $operateName;
            event(new OperateLogEvent($arrayOperateLog));//更新日志

        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        //向知会人发消息

        //向审批人发消息

        return $this->response->item($project, new ProjectTransformer());

    }

    public function edit(EditProjectRequest $request, Project $project)
    {
        $payload = $request->all();
        $arrayOperateLog = [];
        $old_project = clone $project;
        $trail = $project->trail;

        if(!empty($trail))
        {
            $old_trail = clone $trail;
        }
        DB::beginTransaction();
        try {
            if ($request->has('principal_id')) {//负责人
                $payload['principal_id'] = hashid_decode($payload['principal_id']);
                if ($project->principal_id != $payload['principal_id']) {
                    try {
//                        $curr_principal = User::find($project->principal_id)->name;
//                        $principal = User::findOrFail($payload['principal_id'])->name;
//
//                        //操作日志
//                        $operateName = new OperateEntity([
//                            'obj' => $project,
//                            'title' => "负责人",
//                            'start' => $curr_principal,
//                            'end' => $principal,
//                            'method' => OperateLogMethod::UPDATE,
//                        ]);
//                        $arrayOperateLog[] = $operateName;

                    } catch (Exception $e) {
                        Log::error($e);
                        DB::rollBack();
                        return $this->response->errorInternal("负责人错误");
                    }

                }

            }
            if (!$request->has('type') || $payload['type'] == '')
                $payload['type'] = $project->type;


            if ($request->has('fields')) {
                foreach ($payload['fields'] as $key => $val) {
                    $fieldId = hashid_decode((int)$key);
                    $field = TemplateField::where('module_type', $payload['type'])->find($fieldId);
                    if (!$field) {
                        throw new Exception('字段与项目类型匹配错误');
                    }
                }
            }
            if (!$request->has('participant_ids') || !is_array($payload['participant_ids']))
                $payload['participant_ids'] = [];

            if (!$request->has('participant_del_ids') || !is_array($payload['participant_del_ids']))
                $payload['participant_del_ids'] = [];

            //更新之前的项目参与人
            $last_participants = implode(",", array_column($project->participants()->get()->toArray(), 'name'));
            $project->update($payload);//更新项目

            $projectId = $project->id;
//            $trail = $project->trail;
            //只有新增或者要删除的参与人不为空是才更新
            if (count($payload['participant_ids']) != 0 || count($payload['participant_del_ids']) != 0) {
                $this->moduleUserRepository->addModuleUser($payload['participant_ids'], $payload['participant_del_ids'], $project, ModuleUserType::PARTICIPANT);
                //更新之后的项目参与人
                $new_participants = implode(",", array_column($project->participants()->get()->toArray(), 'name'));
                //操作日志
                if (!empty($last_participants) || !empty($new_participants)) {
                    $operateName = new OperateEntity([
                        'obj' => $project,
                        'title' => "项目参与人",
                        'start' => $last_participants,
                        'end' => $new_participants,
                        'method' => OperateLogMethod::UPDATE,
                    ]);
                    $arrayOperateLog[] = $operateName;
                }
            }

            if ($request->has('fields')) {
                foreach ($payload['fields'] as $key => $val) {
                    $fieldId = hashid_decode((int)$key);
                    $fieldValue = FieldValue::where('field_id', $fieldId)->where('project_id', $projectId)->first();

                    //根据filedid获取字段名
                    $fieldName = TemplateField::findOrFail($fieldId)->key;

                    $oldValue = null;
                    if ($fieldValue != null) {
                        $oldValue = $fieldValue->value;
                    }
                    //以前的值不是null并且现在的值也不是null，才进行更新或者新增
                    if (!empty($oldValue) || !empty($val)) {
                        //操作日志
                        if ($oldValue != $val) {
                            $operateName = new OperateEntity([
                                'obj' => $project,
                                'title' => $fieldName,
                                'start' => $oldValue,
                                'end' => $val,
                                'method' => OperateLogMethod::UPDATE,
                            ]);
                            $arrayOperateLog[] = $operateName;
                        }

                        if ($fieldValue) {//存在保存，不存在新增
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

            }

            if ($request->has('trail')) {
                foreach ($payload['trail'] as $key => $val) {
                    if ($key == 'fee') {
//                        $trail->fee = $val;
                        if ($val != $trail->fee) {
//                            $operateName = new OperateEntity([
//                                'obj' => $project,
//                                'title' => "预计订单收入",
//                                'start' => $trail->fee,
//                                'end' => $val,
//                                'method' => OperateLogMethod::UPDATE,
//                            ]);
//                            $arrayOperateLog[] = $operateName;
//                            $operateName = new OperateEntity([
//                                'obj' => $trail,
//                                'title' => "预计订单收入",
//                                'start' => $trail->fee,
//                                'end' => $val,
//                                'method' => OperateLogMethod::UPDATE,
//                            ]);
//                            $arrayOperateLog[] = $operateName;
                        }
                        continue;
                    }
                    if ($key == 'lock') {
//                        $trail->lock_status = $val;

                        if ($val != $trail->lock_status) {
//                            $operateName = new OperateEntity([
//                                'obj' => $project,
//                                'title' => "是否锁价",
//                                'start' => $trail->lock_status == 1 ? "锁价" : "未锁价",
//                                'end' => $val == 1 ? "锁价" : "未锁价",
//                                'method' => OperateLogMethod::UPDATE,
//                            ]);
//                            $arrayOperateLog[] = $operateName;
//
//                            $operateName = new OperateEntity([
//                                'obj' => $trail,
//                                'title' => "是否锁价",
//                                'start' => $trail->lock_status == 1 ? "锁价" : "未锁价",
//                                'end' => $val == 1 ? "锁价" : "未锁价",
//                                'method' => OperateLogMethod::UPDATE,
//                            ]);
//                            $arrayOperateLog[] = $operateName;
                        }
                        continue;
                    }

                    if ($key == 'expectations') {
                        $repository = new TrailStarRepository();
                        //获取现在关联的艺人和博主
                        $start = $repository->getStarListByTrailId($trail->id,TrailStar::EXPECTATION);
                        $repository->deleteTrailStar($trail->id,TrailStar::EXPECTATION);
                        $repository->store($trail,$payload['trail']['expectations'],TrailStar::EXPECTATION);
                        //获取更新之后的艺人和博主列表
                        $end = $repository->getStarListByTrailId($trail->id,TrailStar::EXPECTATION);

                        $title = "关联目标艺人";
                        if (!empty($start) || !empty($end)) {
                            $operateName = new OperateEntity([
                                'obj' => $trail,
                                'title' => $title,
                                'start' => $start,
                                'end' => $end,
                                'method' => OperateLogMethod::UPDATE,
                            ]);
                            $arrayOperateLog[] = $operateName;
                        }

                        continue;
                    }

                    if ($key == 'recommendations') {
                        $repository = new TrailStarRepository();
                        //获取现在关联的艺人和博主
                        $start = $repository->getStarListByTrailId($trail->id,TrailStar::RECOMMENDATION);
                        $repository->deleteTrailStar($trail->id,TrailStar::RECOMMENDATION);
                        $repository->store($trail,$payload['trail']['recommendations'],TrailStar::RECOMMENDATION);
                        //获取更新之后的艺人和博主列表
                        $end = $repository->getStarListByTrailId($trail->id,TrailStar::RECOMMENDATION);

                        $title = "关联推荐艺人";
                        if (!empty($start) || !empty($end)) {
                            $operateName = new OperateEntity([
                                'obj' => $trail,
                                'title' => $title,
                                'start' => $start,
                                'end' => $end,
                                'method' => OperateLogMethod::UPDATE,
                            ]);
                            $arrayOperateLog[] = $operateName;
                        }


                    }

                }
                //更新线索
                $trail->update($payload['trail']);

            }

            event(new OperateLogEvent($arrayOperateLog));//更新日志
            event(new ProjectDataChangeEvent($old_project,$project));//更新项目操作日志

            if(!empty($trail))
            {
            event(new TrailDataChangeEvent($old_trail,$trail));//更新线索操作日志
            }
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('修改失败,' . $exception->getMessage());
        }
        DB::commit();

        DB::beginTransaction();
        try {

            $user = Auth::guard('api')->user();
            $title = $user->name . "将你加入了项目";  //通知消息的标题
            $subheading = $user->name . "将你加入了项目";
            $module = Message::PROJECT;
            $link = URL::action("ProjectController@detail", ["project" => $project->id]);
            $data = [];
            $data[] = [
                "title" => '项目名称', //通知消息中的消息内容标题
                'value' => $project->title,  //通知消息内容对应的值
            ];
            $principal = User::findOrFail($project->principal_id);
            $data[] = [
                'title' => '项目负责人',
                'value' => $principal->name
            ];
            $participant_ids = array_column($project->participants()->select('user_id')->get()->toArray(),'user_id');
            $authorization = $request->header()['authorization'][0];
            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $participant_ids,$project->id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
        }


        return $this->response->accepted();
    }

    public function detail(Request $request, $project,ProjectRepository $repository,ScopeRepository $scopeRepository)
    {
        $type = $project->type;
        $user = Auth::guard("api")->user();
        //登录用户对艺人编辑权限验证
        try{
            //获取用户角色
            $role_list = $user->roles()->pluck('id')->all();
            $scopeRepository->checkPower("projects/{id}",'put',$role_list,$project);
            $project->power = "true";
        }catch (Exception $exception){
            $project->power = "false";
        }

        $project->powers = $repository->getPower($user,$project);
        $result = $this->response->item($project, new ProjectTransformer());
        $data = TemplateField::where('module_type', $type)->get();
        $array['project_kd_name'] = $project->title;
        $array['expense_type'] = '支出';
        $approval  = (new ApprovalContractController())->projectList($request,$project);
        $contractmoney = $approval['money'];
        // 记住修改  收入
        $expendituresum = ProjectBill::where($array)->select(DB::raw('sum(money) as expendituresum'))->groupby('expense_type')->first();
        // 获取目标艺人 所在部门
        if($project->trail){
            $expectations = $project->trail->bloggerExpectations;
            if (count($expectations) <= 0) {
                $expectations = $project->trail->expectations->first();
                if(!$expectations) {
                    return null;
                }else{
                    $expectations = $expectations->broker->toArray();

//                ->broker;
                    $department_name = [];
                    if(!$expectations)
                        return null;
                    foreach ($expectations as $key => $val){
                        $department_name[$key] = DepartmentUser::where('user_id',$val['id'])->first()->department['name'];
                    }
                }

            } else {
                $expectations = $expectations->first()->publicity->toArray();
                $department_name = [];
                if(!$expectations)
                    return null;
                foreach ($expectations as $key => $val){
                    $department_name[$key] = DepartmentUser::where('user_id',$val['id'])->first()->department['name'];
                }
            }

        }
        unset($array);
        $resource = new Fractal\Resource\Collection($data, new TemplateFieldTransformer($project->id));
        $manager = new Manager();
        $manager->setSerializer(new DataArraySerializer());
        $user = Auth::guard('api')->user();
        if($project->trail){
            $result->addMeta('department_name',  $department_name);
        }


        if ($project->creator_id != $user->id && $project->principal_id != $user->id) {

            $contractMoneyResult = PrivacyType::excludePrivacy($user->id,$project->id,ModuleableType::PROJECT, 'contractmoney');
            if(!$contractMoneyResult)
            {
                $result->addMeta('contractmoney', 'privacy');
            }
            else
            {
                if (isset($contractmoney)) {
                    $result->addMeta('contractmoney', "".$contractmoney);
                }
                else
                {
                    $result->addMeta('contractmoney', "".'0');
                }
            }
            $contractMoneyResult = PrivacyType::excludePrivacy($user->id,$project->id,ModuleableType::PROJECT, 'expendituresum');
            if(!$contractMoneyResult)
            {
                $result->addMeta('expendituresum', 'privacy');
            }
            else
            {
                if (isset($expendituresum)) {
                    $result->addMeta('expendituresum', "".$expendituresum->expendituresum);
                }
                else
                {
                    $result->addMeta('expendituresum', "".'0');
                }
            }
        }
        else
        {
            if (isset($contractmoney)) {
                $result->addMeta('contractmoney', "".$contractmoney);
            }
            else
            {
                $result->addMeta('contractmoney', "".'0');
            }
            if (isset($expendituresum)) {
                $result->addMeta('expendituresum', "".$expendituresum->expendituresum);
            }
            else
            {
                $result->addMeta('expendituresum',"".'0');
            }
        }
        $result->addMeta('fields', $manager->createData($resource)->toArray());
        $operate = new OperateEntity([
            'obj' => $project,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate
        ]));
        return $result;
    }

    public function delete(Request $request, Project $project)
    {
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

    public function course(Request $request, Project $project)
    {
        $status = $request->get('status');
        $user = Auth::guard('api')->user();
        $array['user_id'] = $user->id;
        switch ($status) {
            case Project::STATUS_EVALUATINGACCOMPLISH:

                $status1 = $status;

                break;
            case Project::STATUS_CONTRACT:

                $status1 = $status;

                break;
            case Project::STATUS_CONTRACTACCOMPLISH:
                $status1 = $status;

                break;
            case Project::STATUS_EXECUTION:
                $status1 = $status;

                break;
            case Project::STATUS_EXECUTIONACCOMPLISH:
                $status1 = $status;

                break;
            case Project::STATUS_RETURNEDMONEY:
                $status1 = $status;

                break;
            case Project::STATUS_RETURNEDMONEYACCOMPLISH:
                $status1 = $status;

                break;
            case Project::STATUS_BEEVALUATING:
                $status1 = $status;

                break;
            default:
                break;
        }
        $array['logable_id'] = $project->id;
        $array['logable_type'] = 'project';
        $array['content'] = $status1;
        DB::beginTransaction();
        try {

            if (ProjectStatusLogs::where($array)->first() == true) {
                return $this->response->errorForbidden('该状态已存在');
            }

            $projects = ProjectStatusLogs::create($array);

            $operate = new OperateEntity([
                'obj' => $project,
                'title' => null,
                'start' => $project->getProjectStatus($status1),
                'end' => null,
                'method' => OperateLogMethod::FOLLOW_UP,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('失败');
        }
        DB::commit();
        return $this->response->item($projects, new ProjectCourseTransformer());
    }

    public function allCourse(Request $request, Project $project)
    {
        $projects = ProjectStatusLogs::where('logable_id', $project->id)->CreateDesc()->get();
        $count = count($projects->toArray());
        //meta内是项目进度百分比，目前就8步所以除以八
        return $this->response->collection($projects, new ProjectCourseTransformer())->addMeta("progress",$count/(8));
    }

    public function changeStatus(Request $request, Project $project)
    {
        $status = $request->get('status');

        switch ($status) {
            case Project::STATUS_COMPLETE:
                $project->complete_at = now();
                $project->status = $status;
                break;
            case Project::STATUS_FROZEN:
                $project->stop_at = now();
                $project->status = $status;
                $trail = $project->trail;
                if ($trail)
                    $trail->update([
                        'progress_status' => Trail::STATUS_UNCONFIRMED
                    ]);
                //日志
                $operate = new OperateEntity([
                    'obj' => $project,
                    'title' => "撤单",
                    'start' => "暂无原因",
                    'end' => null,
                    'method' => OperateLogMethod::STATUS_FROZEN,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
                break;
            case Project::STATUS_NORMAL:
                $project->stop_at = null;
                $project->complete_at = null;
                $project->status = $status;
                $trail = $project->trail;
                if ($trail)
                    $trail->update([
                        'progress_status' => Trail::STATUS_CONFIRMED
                    ]);
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
        $user = Auth::guard("api")->user();
        $userid = $user->id;

        $projects = Project::where(function ($query) use ($request, $payload,$userid) {
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
            if($request->has('administration'))
                $query->where('principal_id','<>' ,$userid);
            if($request->has('principal_id'))
                $query->where('principal_id',$userid);

            if ($request->has('type') && $payload['type'] <> '3,4'){
                $query->where('type', $payload['type']);
            }
            if($request->has('type') && $payload['type'] == '3,4'){
                $query->whereIn('type',[3,4]);
            }
            if ($request->has('status'))
                $query->where('projects.status', $payload['status']);

        })->searchData()
            ->leftJoin('operate_logs',function($join){
            $join->on('projects.id','operate_logs.logable_id')
            ->where('logable_type',ModuleableType::PROJECT)
            ->where('operate_logs.method','2');
        })->groupBy('projects.id')
            ->orderBy('up_time', 'desc')->orderBy('projects.created_at', 'desc')->select(['projects.id','creator_id','project_number','trail_id','title','projects.type','privacy','projects.status',
                'principal_id','projected_expenditure','priority','start_at','end_at','projects.created_at','projects.updated_at', DB::raw("max(operate_logs.updated_at) as up_time"),'desc'])
            ->paginate($pageSize);
               //  修改项目排序   按跟进时间  和 创建时间排序
        return $this->response->paginator($projects, new ProjectTransformer());

    }


    public function getClient(Request $request)
    {
        $projectId = $request->get('project_id', 0);
        $projectId = hashid_decode($projectId);
        try {
            $project = Project::findOrFail($projectId);
        } catch (Exception $exception) {
            return $this->response->errorBadRequest('项目id错误');
        }

        $client = $project->trail->client;

        $data = array(
            'client_id' => hashid_encode($client->id),
            'title' => $client->company
        );
        return $this->response->array(['data' => array($data)]);
    }

    /**
     * 获取明星下的项目3个
     * @param Request $request
     * @return mixed
     */
    public function getStarProject(Star $star)
    {
        $result = ProjectRepository::getProjectBySatrId($star->id);

        //todo 这里的返回值status没有返回数字，返回的是中文所以用不了transfromer
//        return $this->response->collection($result, new ProjectTransformer());
        return $result;
    }

    public function getClientProject(Request $request, Client $client)
    {
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
            $relate_task = [];
            $relate_project = [];
            if ($request->has('tasks')) {
                ProjectRelate::where('project_id', $project->id)->where('moduleable_type', ModuleableType::TASK)->delete();
                $tasks = $request->get('tasks');
                $tasks = array_unique($tasks);
                foreach ($tasks as $value) {
                    $id = hashid_decode($value);
                    $task = Task::find($id);
                    if ($task) {
                        $relate_task[] = $task->title;
                        ProjectRelate::create([
                            'project_id' => $project->id,
                            'moduleable_id' => $id,
                            'moduleable_type' => ModuleableType::TASK,
                        ]);
                    }

                }
            }

            if ($request->has('projects')) {
                ProjectRelate::where('project_id', $project->id)->where('moduleable_type', ModuleableType::PROJECT)->delete();
                $projects = $request->get('projects');
                $projects = array_unique($projects);
                foreach ($projects as $value) {
                    $id = hashid_decode($value);
                    $temp_project = Project::find($id);
                    if ($temp_project) {
                        $relate_project[] = $temp_project->title;
                        ProjectRelate::create([
                            'project_id' => $project->id,
                            'moduleable_id' => $id,
                            'moduleable_type' => ModuleableType::PROJECT,
                        ]);
                    }

                }
            }
            //记录日志
            $start = null;
            if (count($relate_project) != 0) {
                $start .= implode(",", $relate_project) . "项目";
            }
            if (count($relate_task) != 0) {
                $start .= "," . implode(",", $relate_task) . "任务";
            }

            $operate = new OperateEntity([
                'obj' => $project,
                'title' => null,
                'start' => trim($start, ","),
                'end' => null,
                'method' => OperateLogMethod::ADD_RELATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));

        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('创建关联失败');
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function getMoneType(Request $request)
    {

        $type = ProjectReturnedMoneyType::get();
        return $this->response->collection($type, new ProjectReturnedMoneyTypeTransformer());
    }

    public function indexReturnedMoney(Request $request, Project $project)
    {
        $ploay = $request;
        $approval  = (new ApprovalContractController())->projectList($request,$project);
        $contract_id = $ploay['contract_id'];
//        $contract_id = '20190118274451221';
        $project_id = $project->id;
        $project = ProjectReturnedMoney::where(['contract_id' => $contract_id, 'project_id' => $project_id, 'p_id' => 0])->createDesc()->get();
        $contractReturnedMoney = $approval['money'];

        $alreadyReturnedMoney = ProjectReturnedMoney::where(['contract_id' => $contract_id, 'project_id' => $project_id])->wherein('project_returned_money_type_id', [1, 2, 3, 4])->select(DB::raw('sum(plan_returned_money) as alreadysum'))->createDesc()->first();
        $notReturnedMoney = $contractReturnedMoney - $alreadyReturnedMoney->toArray()['alreadysum'];
        $alreadyinvoice = ProjectReturnedMoney::where(['contract_id' => $contract_id, 'project_id' => $project_id])->wherein('project_returned_money_type_id', [5, 6])->select(DB::raw('sum(plan_returned_money) as alreadysum'))->createDesc()->first();


        $result = $this->response->collection($project, new ProjectReturnedMoneyTransformer());
        $result->addMeta('appoval', $approval);
        $result->addMeta('contractReturnedMoney', $contractReturnedMoney);
        $result->addMeta('alreadyReturnedMoney', $alreadyReturnedMoney->alreadysum);
        $result->addMeta('notReturnedMoney', $notReturnedMoney);
        $result->addMeta('alreadyinvoice', $alreadyinvoice->alreadysum);

        return $result;
    }

    public function addReturnedMoney(ReturnedMoneyRequest $request, Project $project, ProjectReturnedMoney $projectReturnedMoney)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        $payload['creator_id'] = $user->id;
        $array = $payload;
        $array['project_id'] = $project->id;
        if ($request->has('principal_id')) {
            $array['principal_id'] = hashid_decode($payload['principal_id']);
        }
        if ($request->has('project_returned_money_type_id')) {
            $array['project_returned_money_type_id'] = hashid_decode($payload['project_returned_money_type_id']);
        }
        $array['issue_name'] = $projectReturnedMoney->where(['project_id' => $array['project_id'], 'principal_id' => $array['principal_id'], 'p_id' => 0])->count() + 1;
        DB::beginTransaction();
        try {
            $project = ProjectReturnedMoney::create($array);
//            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $project,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
    }

    public function showReturnedMoney(Request $request, ProjectReturnedMoney $projectReturnedMoney)
    {

        if ($projectReturnedMoney->p_id == 0) {
            return $this->response->item($projectReturnedMoney, new ProjectReturnedMoneyTransformer());
        } else {

            return $this->response->item($projectReturnedMoney, new ProjectReturnedMoneyShowTransformer());
        }
    }

    public function editReturnedMoney(EditEeturnedMoneyRequest $request, ProjectReturnedMoney $projectReturnedMoney)
    {
        $payload = $request->all();
        $array = $payload;
        if ($request->has('principal_id')) {

            $array['principal_id'] = hashid_decode($payload['principal_id']);
        }
        if ($request->has('project_returned_money_type_id')) {
            $array['project_returned_money_type_id'] = hashid_decode($payload['project_returned_money_type_id']);
        }
        try {
            $projectReturnedMoney->update($array);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('修改失败,' . $exception->getMessage());
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function deleteReturnedMoney(ProjectReturnedMoney $projectReturnedMoney)
    {

        try {
            $id = ProjectReturnedMoney::where('p_id',$projectReturnedMoney->id)->get(['id'])->toArray();
            if($id){
                ProjectReturnedMoney::whereIn('id',$id)->delete();
            }else{
                $projectReturnedMoney->delete();
            }
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('删除失败');
        }

        return $this->response->noContent();

    }

    public function addProjectRecord(Request $request, Project $project, ProjectReturnedMoney $projectReturnedMoney)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        $payload['creator_id'] = $user->id;
        $array = $payload;
        $array['project_id'] = $project->id;
        $array['p_id'] = $projectReturnedMoney->id;
        if ($request->has('principal_id')) {
            $array['principal_id'] = hashid_decode($payload['principal_id']);
        }
        if ($request->has('project_returned_money_type_id')) {
            $array['project_returned_money_type_id'] = hashid_decode($payload['project_returned_money_type_id']);
        }
        $array['issue_name'] = $projectReturnedMoney->where(['project_id' => $array['project_id'], 'principal_id' => $array['principal_id'], 'p_id' => $projectReturnedMoney->id])->count() + 1;
        DB::beginTransaction();
        try {
            $project = ProjectReturnedMoney::create($array);
//            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $project,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
    }

    private function editLog($obj, $field, $old, $new)
    {
        $operate = new OperateEntity([
            'obj' => $obj,
            'title' => $field,
            'start' => $old,
            'end' => $new,
            'method' => OperateLogMethod::UPDATE,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
    }

    /**
     * 获取已经审批通过的项目
     */
    public function getHasApprovalProject()
    {
        $res = Project::select('projects.id','projects.title')->Join('approval_form_business','projects.project_number','approval_form_business.form_instance_number')
            ->searchData()
            ->where('form_status',232)//232 签约通过
            ->get();
        return $this->response->collection($res,new simpleProjectTransformer());
    }

    function str_insert($str, $i, $substr)
    {
        $startstr = [];
        $laststr = [];
        for($j=0; $j<$i; $j++){
            $startstr .= $str[$j];
        }
        for ($j=$i; $j<strlen($str); $j++){
            $laststr .= $str[$j];
        }
        $str = ($startstr . $substr . $laststr);
        return $str;
    }
    /**
     * 暂时不用列表了，逻辑要换
     * @param FilterRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function getFilter(FilterRequest $request)
    {
        $payload = $request->all();
//        $joinSql = '`projects`';
        $joinSql = FilterJoin::where('table_name', 'projects')->first()->join_sql;
        if($request->has('conditions')){
            foreach ($payload['conditions'] as $v => $k){
                if($k['type'] == 5);
                {
                    unset($payload['conditions'][$v]);
                }
                if($k['field'] == 'th.value'){
                   $arr =  'left join `template_field_value_histories` as th on th.project_id = projects.id and th.field_id = 22';
                   $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'cc.value'){
                    $arr =  'left join `template_field_value_histories` as cc on cc.project_id = projects.id and cc.field_id = 52 ';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'clo.value'){
                    $arr =  'left join `template_field_value_histories` as clo on clo.project_id = projects.id and clo.field_id = 11';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'tcs.value'){
                    $arr =  'left join `template_field_value_histories` as tcs on tcs.project_id = projects.id and tcs.field_id = 31';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'tc.value'){
                    $arr =  'left join `template_field_value_histories` as tc on tc.project_id = projects.id and tc.field_id = 32';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'tl.value'){
                    $arr =  'left join `template_field_value_histories` as tl on tl.project_id = projects.id and tl.field_id = 34';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'tt.value'){
                    $arr =  'left join `template_field_value_histories` as tt on tt.project_id = projects.id and tt.field_id = 7';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'tq.value'){
                    $arr =  'left join `template_field_value_histories` as tq on tq.project_id = projects.id and tq.field_id = 9';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'tx.value'){
                    $arr =  'left join `template_field_value_histories` as tx on tx.project_id = projects.id and tx.field_id = 23';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'tg.value'){
                    $arr =  'left join `template_field_value_histories` as tg on tg.project_id = projects.id and tg.field_id = 24';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'td.value'){
                    $arr =  'left join `template_field_value_histories` as td on td.project_id = projects.id and td.field_id = 25';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'tr.value'){
                    $arr =  'left join `template_field_value_histories` as tr on tr.project_id = projects.id and tr.field_id = 26';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'trd.value'){
                    $arr =  'left join `template_field_value_histories` as trd on trd.project_id = projects.id and trd.field_id = 27';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'cl.value'){
                    $arr =  'left join `template_field_value_histories` as cl on cl.project_id = projects.id and cl.field_id = 28';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'co.value'){
                    $arr =  'left join `template_field_value_histories` as co on co.project_id = projects.id and co.field_id = 55';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'cp.value'){
                    $arr =  'left join `template_field_value_histories` as cp on cp.project_id = projects.id and cp.field_id = 54';
                    $joinSql = $joinSql."$arr";
                }
                if($k['field'] == 'mu.user_id'){
                    $arr =  'left join `module_users` as mu on mu.moduleable_id = projects.id and mu.moduleable_type = \'project\' and mu.type = 1';
                    $joinSql = $joinSql."$arr";
                }
            }
        }
        $pageSize = $request->get('page_size', config('app.page_size'));
      //  $joinSql = FilterJoin::where('table_name', 'projects')->first()->join_sql;


        $query = Project::selectRaw('DISTINCT(projects.id) as ids')->from(DB::raw($joinSql));
        $projects = $query->where(function ($query) use ($payload) {
            FilterReportRepository::getTableNameAndCondition($payload,$query);
        });
        $all = $request->get('all', false);
        $user = Auth::guard('api')->user();
        $project_type = $request->get('project_type',null);
        $query =  $projects->where(function ($query) use ($request, $payload,$user,$project_type) {
            if ($request->has('keyword'))
                $query->where('projects.title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('projects.principal_id', $payload['principal_ids']);
            }
            if ($request->has('project_type') && $project_type <> '3,4' ){
                $query->where('projects.type',$project_type);
            }
            if($request->has('project_type') && $project_type == '3,4'){
                $query->whereIn('projects.type',[$project_type]);
            }
            if ($request->has('status'))#项目状态
                $query->where('projects.status', $payload['status']);
        });
        if ($request->has('my')){
            switch ($payload['my']){
                case 'my_principal'://我负责
                    $query->where('projects.principal_id', $user->id);
                    break;
                case 'my_participant'://我参与
                    $query->leftJoin("module_users as mu2",function ($join){
                        $join->on("mu2.moduleable_id","projects.id")
                            ->where('mu2.moduleable_type',ModuleableType::PROJECT);
                    })->where('mu2.user_id',$user->id);
                    break;
                case 'my_create'://我创建
                    $query->where('projects.creator_id', $user->id);
                    break;

            }
        }
        $projects = $query->searchData()->groupBy('projects.id')
            ->get();
        $projects = Project::whereIn('projects.id', $projects)
        ->leftJoin('operate_logs',function($join){
            $join->on('projects.id','operate_logs.logable_id')
                ->where('logable_type',ModuleableType::PROJECT)
                ->where('operate_logs.method','4');
        })->groupBy('projects.id')
        ->orderBy('up_time', 'desc')->orderBy('projects.created_at', 'desc')->select(['projects.id','projects.creator_id','projects.project_number','projects.trail_id','projects.title','projects.type','projects.privacy','projects.status',
            'projects.principal_id','projected_expenditure','projects.priority','projects.start_at','projects.end_at','projects.created_at','projects.updated_at', DB::raw("max(operate_logs.updated_at) as up_time"),'desc'])
//// 这句用来检查绑定的参数
//        $sql_with_bindings = str_replace_array('?', $projects->getBindings(), $projects->toSql());
////
//        dd($sql_with_bindings);
        ->paginate($pageSize);
        return $this->response->paginator($projects, new ProjectTransformer(!$all));
    }

    public function getProjectList(Request $request,Star $star,Blogger $blogger)
    {
        if ($star->id){
            $star_type = "stars";
            $id = $star->id;
            $name = $star->name;
        }else{
            $star_type = "bloggers";
            $id = $blogger->id;
            $name = $star->nickname;
        }
        $pageSize = $request->get('page_size', config('app.page_size'));
        $projects = ProjectRepository::getSignContractProjectBySatr($id,$name,$star_type,$pageSize);
        return $this->response->paginator($projects,new StarProjectTransformer());
    }

    public function export(Request $request)
    {

        $file = '当前项目导出' . date('YmdHis', time()) . '.xlsx';
        return (new ProjectsExport($request))->download($file);
    }


    public function dashboard(Request $request, Department $department)
    {
        $days = $request->get('days', 7);
        $departmentId = $department->id;
        $departmentArr = Common::getChildDepartment($departmentId);
        $userIds = DepartmentUser::whereIn('department_id', $departmentArr)->pluck('user_id');

        $projects = Project::select('projects.id as id', DB::raw('GREATEST(projects.created_at, COALESCE(MAX(operate_logs.created_at), 0)) as t'), 'projects.title')
            ->whereIn('projects.principal_id', $userIds)
            ->leftjoin('operate_logs', function ($join) {
                $join->on('projects.id', '=', 'operate_logs.logable_id')
                    ->where('operate_logs.logable_type', ModuleableType::PROJECT)
                    ->where('operate_logs.method', OperateLogMethod::FOLLOW_UP);
            })->groupBy('projects.id')
            ->orderBy('t', 'desc')
            ->take(5)->get();

        $result = $this->response->collection($projects, new DashboardModelTransformer());


        $count = Project::whereIn('principal_id', $userIds)->count('id');
        $completeCount = Project::whereIn('principal_id', $userIds)->where('status', Project::STATUS_COMPLETE)->count('id');

        $timePoint = Carbon::today('PRC')->subDays($days);

        $signed = Project::whereIn('principal_id', $userIds)
            ->join('contracts', function ($join) {
                $join->on('contracts.project_id', '=', 'projects.id');
            })
            ->count();

        $latestFollow = Project::whereIn('principal_id', $userIds)->join('operate_logs', function ($join) {
            $join->on('projects.id', '=', 'operate_logs.logable_id')
                ->where('operate_logs.logable_type', ModuleableType::PROJECT)
                ->where('operate_logs.method', OperateLogMethod::FOLLOW_UP);
        })->where('operate_logs.created_at', '>', $timePoint)->distinct('projects.id')->count('projects.id');

        $projectInfoArr = [
            'total' => $count,
            'latest_follow' => $latestFollow,
            'completed' => $completeCount,
            'signed' => $signed,
        ];

        $result->addMeta('count', $projectInfoArr);
        return $result;
    }

    public function list(FilterRequest $request)
    {
        # 权限|我负责的|我参与的|自定义筛选|整理数据格式

        # 我参与的
        $power = Project::getConditionSql();
//        DB::table('module_users')->where('user_id', $id)->where('moduleable_type', ModuleableType::PROJECT)->pluck('moduleable_id')->toArray();
//        $user = Auth::guard('api')->user();

        $query = DB::table('project_implode')->select('*');
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        if ($request->has('my')){
            switch ($payload['my']){
                case 'my_principal'://我负责
                    $query->where('principal_id', $user->id);
                    break;
                case 'my_participant'://我参与
                    $query->leftJoin("module_users as mu2",function ($join){
                        $join->on("mu2.moduleable_id","projects.id")
                            ->where('mu2.moduleable_type',ModuleableType::PROJECT);
                    })->where('mu2.user_id',$user->id);
                    break;
                case 'my_create'://我创建
                    $query->where('creator_id', $user->id);
                    break;

            }
        }
        $query->whereRaw(DB::raw("1 = 1 $power"));
        $paginator = $query->paginate();
        $projects = $paginator->getCollection();
        $resource = new Fractal\Resource\Collection($projects, function ($item) {
            # 单独处理
            $stars = [];
            if ($item->stars) {

                $arr["stars"] = explode(',',$item->stars);
                $arr["star_ids"] = explode(',',$item->star_ids);
                foreach ($arr['stars'] as $key1 => $val1) {
                    $stars[] = [
                        'id' => hashid_encode($arr['star_ids'][$key1]),
                        'name' => $val1
                    ];
                }
            }
            $bloggers = [];
            if ($item->bloggers) {
                $arr["blogger_ids"] = explode(',',$item->blogger_ids);
                $arr["bloggers"] = explode(',', $item->bloggers);
                foreach ($arr['bloggers'] as $key2 => $val2) {
                    $bloggers[] = [
                        'id' => hashid_encode($arr['blogger_ids'][$key2]),
                        'name' => $val2
                    ];
                }
            }
            $expectations = array_merge($stars, $bloggers);
            return [
                "id" => hashid_encode($item->id),
                "title" => $item->project_name,
                "type" => $item->project_type,
                "priority" => $item->project_priority,
                "start_at" => $item->project_start_at,
                "end_at" => $item->project_end_at,
                "created_at" => $item->project_store_at,
                "status" => $item->project_status,
                "last_follow_up_at" => $item->last_follow_up_at,
                "last_updated_at" => $item->last_follow_up_at,
                "principal" => [
                    'data' => [
                        'id' => hashid_encode($item->principal_id),
                        "name" => $item->principal,
                        "department" => [
                            "id" => hashid_encode($item->department_id),
                            "name" => $item->department,
                        ]
                    ]
                ],
                "creator" => [
                    'data' => [
                        'id' => hashid_encode($item->creator_id),
                        "name" => $item->creator,
                    ]
                ],
                "trail" => [
                    "data" => [
                        "resource_type" => $item->resource_type,
                        "fee" => $item->trail_fee,
                        "cooperation_type" => $item->cooperation_type,
                        "status" => $item->trail_status,
                        "expectations" => [
                            "data" => $expectations
                        ],
                    ]
                ],
//                "sign_at" => $item->sign_at,
//                "launch_at" => $item->launch_at,
//                "platforms" => $item->platforms,
//                "show_type" => $item->show_type,
//                "guest_type" => $item->guest_type,
//                "record_at" => $item->record_at,
//                "movie_type" => $item->movie_type,
//                "theme" => $item->theme,
//                "team_info" => $item->team_info,
//                "follow_up" => $item->follow_up,
//                "walk_through_at" => $item->walk_through_at,
//                "walk_through_location" => $item->walk_through_location,
//                "walk_through_feedback" => $item->walk_through_feedback,
//                "follow_up_result" => $item->follow_up_result,
//                "agreement_fee" => $item->agreement_fee,
//                "multi_channel" => $item->multi_channel,

                "client" => $item->client,
                "last_follow_up_user_id" => $item->last_follow_up_user_id,
                "last_follow_up_user_name" => $item->last_follow_up_user_name,

                "projected_expenditure" => $item->projected_expenditure,
                "expenditure" => $item->expenditure,
                "revenue" => $item->revenue,
            ];
        });
        $data = $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        $manager = new Manager();
        return response($manager->createData($data)->toArray());
    }
}

