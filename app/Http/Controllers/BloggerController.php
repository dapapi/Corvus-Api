<?php

namespace App\Http\Controllers;

use App\Events\BloggerDataChangeEvent;
use App\Events\TaskMessageEvent;
use App\Gender;
use App\Helper\Common;
use App\Http\Transformers\BloggerDetailTransformer;
use App\Http\Transformers\BloggerListTransformer;
use App\Http\Transformers\DashboardModelTransformer;
use App\Models\Department;
use App\Models\Project;
use App\Models\TaskType;
use App\Exports\BloggersExport;
use App\Http\Requests\BloggerRequest;
use App\Http\Requests\BloggerUpdateRequest;
use App\Http\Requests\BloggerProductionRequest;
use App\Http\Requests\BloggerProducerRequest;
use App\Http\Requests\Filter\FilterRequest;
use App\Http\Transformers\BloggerTransformer;
use App\Http\Transformers\BloggerDepartmentUserTransformer;
use App\Http\Transformers\BloggerTypeTransformer;
use App\Http\Transformers\ProductionTransformer;
use App\Http\Transformers\ReviewQuestionnaireShowTransformer;
use App\Http\Requests\Excel\ExcelImportRequest;
use App\Imports\BloggersImport;
use App\Models\Blogger;
use App\Models\Production;
use App\Models\FilterJoin;
use App\Models\DepartmentUser;
use App\Models\BloggerType;
use App\Models\ModuleUser;
use App\Models\ReviewQuestion;
use App\Models\ReviewQuestionItem;
use App\Models\ReviewUser;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleUserType;

use App\Repositories\BloggerRepository;
use App\Repositories\ScopeRepository;

use App\ReviewItemAnswer;
use App\Models\ReviewQuestionnaire;
use App\Models\StarWeiboshuInfo;
use App\Models\StarXiaohongshuInfo;
use App\Models\StarDouyinInfo;
use App\Models\BloggerProducer;
use App\Events\OperateLogEvent;
use App\Models\Task;
use App\ModuleableType;
use App\Models\TaskResource;
use App\Repositories\OperateLogRepository;
use App\Repositories\FilterReportRepository;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\SignContractStatus;
use App\Traits\PrivacyFieldTrait;
use App\TriggerPoint\TaskTriggerPoint;
use App\User;
use App\Whether;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
class BloggerController extends Controller
{

    protected $filterReportRepository;
    public function __construct(OperateLogRepository $operateLogRepository)
    {
        $this->operateLogRepository = $operateLogRepository;

    }

    public function index(Request $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', config('app.status'));
        $array = [];//查询条件
        //合同
        $status = empty($status) ? $array[] = ['sign_contract_status',1] : $array[] = ['sign_contract_status',$status];
        if($request->has('name')){//姓名
            $array[] = ['nickname','like','%'.$payload['name'].'%'];
        }

        if($request->has('type')){//类型
            $array[] = ['type_id',hashid_decode($payload['type'])];
        }
        if($request->has('communication_status')){//沟通状态
            $array[] = ['communication_status',$payload['communication_status']];
        }
        // sign_contract_status   签约状态
        $bloggers = Blogger::where($array)->searchData()->leftJoin('operate_logs',function($join){
            $join->on('bloggers.id','operate_logs.logable_id')
                ->where('logable_type',ModuleableType::BLOGGER)
                ->where('operate_logs.method','4');
        })->groupBy('bloggers.id')
            ->orderBy('up_time', 'desc')->orderBy('bloggers.created_at', 'desc')->select(['bloggers.id','nickname','platform_id','communication_status','intention','intention_desc','sign_contract_at','bloggers.level',
                'hatch_star_at','bloggers.status','hatch_end_at','producer_id','sign_contract_status','icon','type_id','desc','avatar','creator_id','gender','cooperation_demand','terminate_agreement_at','sign_contract_other',
                'bloggers.updated_at','bloggers.created_at','sign_contract_other_name',DB::raw("max(operate_logs.updated_at) as up_time")])
            ->paginate($pageSize);
//                $sql_with_bindings = str_replace_array('?', $bloggers->getBindings(), $bloggers->toSql());
//        dd($sql_with_bindings);
        return $this->response->paginator($bloggers, new BloggerTransformer());
    }
    public function all(Request $request)
    {
        $isAll = $request->get('all', false);
        $payload = $request->all();
        $array = [];//查询条件
        if ($request->has('sign_contract_status') && !empty($payload['sign_contract_status'])) {//签约状态

            $array[] = ['sign_contract_status', $payload['sign_contract_status']];
        }
        $bloggers = Blogger::where($array)->createDesc()->searchData()->get();
        return $this->response->collection($bloggers, new BloggerTransformer($isAll));
    }
    public function gettypename(Request $request)
    {
        $payload = $request->all();

        $bloggers = BloggerType::get();
        return $this->response->collection($bloggers, new BloggerTypeTransformer());
    }


    public function show(Blogger $blogger,BloggerRepository $repository,ScopeRepository $scopeRepository)
    {
        // 操作日志
//        $operate = new OperateEntity([
//            'obj' => $blogger,
//            'title' => null,
//            'start' => null,
//            'end' => null,
//            'method' => OperateLogMethod::LOOK,
//        ]);
//        event(new OperateLogEvent([
//            $operate,
//        ]));

        $user = Auth::guard("api")->user();
        //登录用户对博主编辑权限验证
        try{
            //获取用户角色
            $role_list = $user->roles()->pluck('id')->all();
            $res = $scopeRepository->checkPower("bloggers/{id}",'put',$role_list,$blogger);
            $blogger->power = "true";
        }catch (Exception $exception){
            $blogger->power = "false";
        }
        $blogger->powers = $repository->getPower($user,$blogger);
        return $this->response->item($blogger, new BloggerTransformer());
    }
    public function recycleBin(Request $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $blogger = Blogger::onlyTrashed()->searchData()->paginate($pageSize);
        return $this->response->paginator($blogger, new BloggerTransformer());
    }
    public function remove(Blogger $blogger)
    {
        DB::beginTransaction();
        try {
            $blogger->delete();
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $blogger,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::DELETE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();
    }
    public function recoverRemove(Blogger $blogger)
    {
        DB::beginTransaction();
        try {
            $blogger->restore();
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $blogger,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::RECOVER,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('恢复博主失败');
        }
        DB::commit();
    }

    //选择成员
    public function select(Request $request,DepartmentUser $departmentuser)
    {
        $user = Auth::guard('api')->user();
        $payload = $request->all();

        if($request->has('searchid')){
            $searchid = hashid_decode($payload['searchid']);
        }else{
            $searchid = $user->id;
        }
        $pageSize = $request->get('page_size', config('app.page_size'));
        $depatments = DepartmentUser::where('user_id', $searchid)->get(['department_id']);

        $users = $departmentuser->whereIn('department_id', $depatments)->paginate($pageSize);
        return $this->response->paginator($users, new BloggerDepartmentUserTransformer());

    }

    public function edit(BloggerUpdateRequest $request, Blogger $blogger)
    {
        $payload = $request->all();
        $array = [];
        $arrayOperateLog = [];
        $old_blogger = clone $blogger;
        if ($request->has('nickname')) {
            $array['nickname'] = $payload['nickname'];
            if ($array['nickname'] != $blogger->nickname) {
//                $operateNickname = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '昵称',
//                    'start' => $blogger->nickname,
//                    'end' => $array['nickname'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['nickname']);
            }
        }
        if ($request->has('type_id')) {
            try {
                $start = $blogger->type->name;
                $array['type_id'] = hashid_decode($payload['type_id']);
                $typeId = hashid_decode($payload['type_id']);
                $bloggerType = BloggerType::findOrFail($typeId);
                $end = $bloggerType->name;
                if ($start != $end) {
//                    $operateBloggerType = new OperateEntity([
//                        'obj' => $blogger,
//                        'title' => '类型',
//                        'start' => $start,
//                        'end' => $end,
//                        'method' => OperateLogMethod::UPDATE,
//                    ]);
//                    $arrayOperateLog[] = $operateBloggerType;
                } else {
                    unset($array['type_id']);
                }
            } catch (Exception $e) {
                return $this->response->errorBadRequest('博主类型错误');
            }
        }
        if ($request->has('communication_status')) {
            $array['communication_status'] = $payload['communication_status'];
            if ($array['communication_status'] != $blogger->communication_status) {

//                $start = CommunicationStatus::getStr($blogger->communication_status);
//                $end = CommunicationStatus::getStr($array['communication_status']);
//
//                $operateCommunicationStatus = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '沟通状态',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateCommunicationStatus;
            } else {
                unset($array['communication_status']);
            }
        }

        if ($request->has('intention')) {
            $array['intention'] = $payload['intention'];
            if ($array['intention'] != $blogger->intention) {

//                $start = Whether::getStr($blogger->intention);
//                $end = Whether::getStr($array['intention']);
//
//                $operateIntention = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '与我司签约意向',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateIntention;
            } else {
                unset($array['intention']);
            }
        }

        if ($request->has('intention_desc')) {
            $array['intention_desc'] = $payload['intention_desc'];
            if ($array['intention_desc'] != $blogger->intention_desc) {
//                $operateIntentionDesc = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '不与我司签约原因',
//                    'start' => $blogger->intention_desc,
//                    'end' => $array['intention_desc'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateIntentionDesc;
            } else {
                unset($array['intention_desc']);
            }
        }

        if ($request->has('sign_contract_at')) {
            $array['sign_contract_at'] = $payload['sign_contract_at'];
            if ($array['sign_contract_at'] != $blogger->sign_contract_at) {
//                $operateSignContractAt = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '签约日期',
//                    'start' => $blogger->sign_contract_at,
//                    'end' => $array['sign_contract_at'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateSignContractAt;
            } else {
                unset($array['sign_contract_at']);
            }
        }

        if ($request->has('level')) {
            $array['level'] = $payload['level'];
            if ($array['level'] != $blogger->level) {

//                $start = BloggerLevel::getStr($blogger->level);
//                $end = BloggerLevel::getStr($array['level']);
//
//                $operateLevel = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '级别',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateLevel;
            } else {
                unset($array['level']);
            }
        }
        if ($request->has('hatch_star_at')) {
            $array['hatch_star_at'] = $payload['hatch_star_at'];
            if ($array['hatch_star_at'] != $blogger->hatch_star_at) {
//                $operateHatchStarAt = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '孵化期开始时间',
//                    'start' => $blogger->hatch_star_at,
//                    'end' => $array['hatch_star_at'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateHatchStarAt;
            } else {
                unset($array['hatch_star_at']);
            }
        }

        if ($request->has('hatch_end_at')) {
            $array['hatch_end_at'] = $payload['hatch_end_at'];
            if ($array['hatch_end_at'] != $blogger->hatch_end_at) {
//                $operateHatchEndAt = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '孵化期结束时间',
//                    'start' => $blogger->hatch_end_at,
//                    'end' => $array['hatch_end_at'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateHatchEndAt;
            } else {
                unset($array['hatch_end_at']);
            }
        }

        if ($request->has('producer_id')) {
            try {
                $start = null;
                if ($blogger->producer_id) {
                    $currentProducer = User::find($blogger->producer_id);
                    if ($currentProducer)
                        $start = $currentProducer->name;
                }

                $producerId = hashid_decode($payload['producer_id']);
                $producerUser = User::findOrFail($producerId);
                $array['producer_id'] = $producerUser->id;

                if ($producerUser->id != $array['producer_id']) {
//                    $operateProducer = new OperateEntity([
//                        'obj' => $blogger,
//                        'title' => '制作人',
//                        'start' => $start,
//                        'end' => $producerUser->name,
//                        'method' => OperateLogMethod::UPDATE,
//                    ]);
//                    $arrayOperateLog[] = $operateProducer;
                } else {
                    unset($array['producer_id']);
                }
            } catch (Exception $e) {
                return $this->response->errorBadRequest('制作人错误');
            }
        }

//        //TODO 此状态只能在合同改变时改变
//        if ($request->has('sign_contract_status')) {
//            $array['sign_contract_status'] = $payload['sign_contract_status'];
//            if ($array['sign_contract_status'] != $blogger->sign_contract_status) {
//
//                $start = SignContractStatus::getStr($blogger->sign_contract_status);
//                $end = SignContractStatus::getStr($array['sign_contract_status']);
//
//                $operateSignContractStatus = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '签约状态',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateSignContractStatus;
//            } else {
//                unset($array['sign_contract_status']);
//            }
//        }

        if ($request->has('desc')) {
            $array['desc'] = $payload['desc'];
            if ($array['desc'] != $blogger->desc) {
//                $operateDesc = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '描述',
//                    'start' => $blogger->desc,
//                    'end' => $array['desc'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateDesc;
            } else {
                unset($array['desc']);
            }
        }
        if ($request->has('avatar')) {
            $array['avatar'] = $payload['avatar'];
            if ($array['avatar'] != $blogger->avatar) {
//                $operateAvatar = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '头像',
//                    'start' => null,
//                    'end' => null,
//                    'method' => OperateLogMethod::RENEWAL,
//                ]);
//                $arrayOperateLog[] = $operateAvatar;
            } else {
                unset($array['avatar']);
            }
        }
        if ($request->has('gender')) {
            $array['gender'] = $payload['gender'];
            if ($array['gender'] != $blogger->gender) {

                $start = Gender::getStr($blogger->gender);
                $end = Gender::getStr($array['gender']);

//                $operateGender = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '性别',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateGender;
            } else {
                unset($array['gender']);
            }
        }

        if ($request->has('cooperation_demand')) {
            $array['cooperation_demand'] = $payload['cooperation_demand'];
            if ($array['cooperation_demand'] != $blogger->cooperation_demand) {
//                $operateCooperationDemand = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '合作需求',
//                    'start' => $blogger->cooperation_demand,
//                    'end' => $array['cooperation_demand'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateCooperationDemand;
            } else {
                unset($array['cooperation_demand']);
            }
        }

        if ($request->has('terminate_agreement_at')) {
            $array['terminate_agreement_at'] = $payload['terminate_agreement_at'];
            if ($array['terminate_agreement_at'] != $blogger->terminate_agreement_at) {
//                $operateTerminateAgreementAt = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '解约日期',
//                    'start' => $blogger->terminate_agreement_at,
//                    'end' => $array['terminate_agreement_at'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateTerminateAgreementAt;
            } else {
                unset($array['terminate_agreement_at']);
            }
        }

        if ($request->has('sign_contract_other')) {
            $array['sign_contract_other'] = $payload['sign_contract_other'];
            if ($array['sign_contract_other'] != $blogger->sign_contract_other) {

//                $start = Whether::getStr($blogger->sign_contract_other);
//                $end = Whether::getStr($array['sign_contract_other']);
//
//                $operateSignContractOther = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '是否签约其他公司',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateSignContractOther;
            } else {
                unset($array['sign_contract_other']);
            }
        }

        if ($request->has('sign_contract_other_name')) {
            $array['sign_contract_other_name'] = $payload['sign_contract_other_name'];
            if ($array['sign_contract_other_name'] != $blogger->sign_contract_other_name) {
//                $operateSignContractOtherName = new OperateEntity([
//                    'obj' => $blogger,
//                    'title' => '签约公司名称',
//                    'start' => $blogger->sign_contract_other_name,
//                    'end' => $array['sign_contract_other_name'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateSignContractOtherName;
            } else {
                unset($array['sign_contract_other_name']);
            }
        }
        if(!empty($payload['platform'])){
            $array['platform']  = $payload['platform'];
        }

        if(!empty($payload['star_douyin_infos'])){
            $array['douyin_id']  = $payload['star_douyin_infos']['url'];
            $array['douyin_fans_num']  = $payload['star_douyin_infos']['avatar'];
        }

        if(!empty($payload['star_weibo_infos'])){
            $array['weibo_url']  = $payload['star_weibo_infos']['url'];
            $array['weibo_fans_num']  = $payload['star_weibo_infos']['avatar'];
        }

        if(!empty($payload['star_xiaohongshu_infos'])){
            $array['xiaohongshu_url']  = $payload['star_xiaohongshu_infos']['url'];
            $array['xiaohongshu_fans_num']  = $payload['star_xiaohongshu_infos']['avatar'];
        }
        DB::beginTransaction();
        try {
            if (count($array) == 0)
                return $this->response->noContent();
            $blogger->update($array);
            // 操作日志
//            event(new OperateLogEvent($arrayOperateLog));
            event(new BloggerDataChangeEvent($old_blogger,$blogger));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();

        return $this->response->accepted();
    }

    public function store(BloggerRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);

        $payload['creator_id'] = $user->id;

        if ($request->has('producer_id')) {
            try {
//                $producerId = hashid_decode($payload['producer_id']);
//                Blogger::findOrFail($producerId);
                $payload['producer_id'] = hashid_decode($payload['producer_id']);

            } catch (Exception $e) {
                return $this->response->errorBadRequest('制作人错误');
            }
        }

        if(!empty($payload['star_douyin_infos'])){
            $payload['douyin_id']  = $payload['star_douyin_infos']['url'];
            $payload['douyin_fans_num']  = $payload['star_douyin_infos']['avatar'];
        }

        if(!empty($payload['star_weibo_infos'])){
            $payload['weibo_url']  = $payload['star_weibo_infos']['url'];
            $payload['weibo_fans_num']  = $payload['star_weibo_infos']['avatar'];
        }

        if(!empty($payload['star_xiaohongshu_infos'])){
            $payload['xiaohongshu_url']  = $payload['star_xiaohongshu_infos']['url'];
            $payload['xiaohongshu_fans_num']  = $payload['star_xiaohongshu_infos']['avatar'];
        }
        if ($request->has('type_id')) {
            try {
                $typeId = hashid_decode($payload['type_id']);
                BloggerType::findOrFail($typeId);
                $payload['type_id'] = $typeId;
            } catch (Exception $e) {
                return $this->response->errorBadRequest('类型错误');
            }
        }
        DB::beginTransaction();
        try {

            $blogger = Blogger::create($payload);

//            if(!empty($payload['star_douyin_infos'])){
//                $stardouyininfo = StarDouyinInfo::create($payload['star_douyin_infos']);
//            }
//            if(!empty($payload['star_weibo_infos'])){
//                $StarWeiboshuInfo= new StarWeiboshuInfo;
//                $StarWeiboshuInfo ->  open_id = $payload['star_douyin_infos']['open_id'];
//                $StarWeiboshuInfo ->  url = $payload['star_douyin_infos']['url'];
//                $StarWeiboshuInfo ->  nickname = $payload['star_douyin_infos']['nickname'];
//                $StarWeiboshuInfo ->  avatar = $payload['star_douyin_infos']['avatar'];
//                $StarWeiboshuInfo ->  save();
//            }
//            if(!empty($payload['star_xiaohongshu_infos'])){
//
//                $starxiaohongshuinfos = new StarXiaohongshuInfo;
//                $starxiaohongshuinfos['open_id'] = $payload['star_xiaohongshu_infos']['open_id'];
//                $starxiaohongshuinfos['url'] = $payload['star_xiaohongshu_infos']['url'];
//                $starxiaohongshuinfos['nickname'] = $payload['star_xiaohongshu_infos']['nickname'];
//                $starxiaohongshuinfos['avatar'] = $payload['star_xiaohongshu_infos']['avatar'];
//                $starxiaohongshuinfos ->  save();
//
//            }

            // 操作日志
            $operate = new OperateEntity([
                'obj' => $blogger,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

        return $this->response->item(Blogger::find($blogger->id), new BloggerTransformer());
    }
    public function producerStore(BloggerProducerRequest $request,Blogger $blogger)
    {
        $payload = $request->all();
        if ($request->has('producer_id')) {
            try {
                $producerId = hashid_decode($payload['producer_id']);
                Blogger::findOrFail($producerId);
                $payload['producer_id'] = $producerId;
            } catch (Exception $e) {
                return $this->response->errorBadRequest('制作人错误');
            }
        }
        DB::beginTransaction();
        try {
            $blogger = $blogger->update($payload);
            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $blogger,
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
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function productionStore(BloggerProductionRequest $request,ReviewQuestionnaire $reviewquestionnaire,ReviewQuestion $reviewquestion)
    {
        $payload = $request->all();
        $blooger_id = hashid_decode($payload['blogger_id']);
        $blogger = Blogger::withTrashed()->findOrFail($blooger_id);
        unset($payload['blogger_id']);
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $array['creator_id'] = $user->id;

        DB::beginTransaction();
        try {
            $production = Production::create($payload);
            $model = new BloggerProducer;
            $model->blogger_id =$blooger_id;
            $model->producer_id =$production->id;
            $m = $model->save();
            if (!empty($array['creator_id'])) {
                $department_id = DepartmentUser::where('user_id',$array['creator_id'])->first()->department_id;
                $users = DepartmentUser::where('department_id',$department_id)->get(['user_id'])->toArray();
                if(isset($users)){
                    foreach($users as $key => $val){
                        $moduleuser = new ModuleUser;
                        $moduleuser->user_id = $val['user_id'];
                        $moduleuser->moduleable_id = $production->id;
                        $moduleuser->moduleable_type = 'reviewquestionnaire';
                        $moduleuser->type = 1;  //1  参与人
                        $modeluseradd = $moduleuser->save();
                     }
                }
                //获取视频评分调查问卷截止时间
                $number = date("w",time());  //当时是周几
                $number = $number == 0 ? 7 : $number; //如遇周末,将0换成7
                $diff_day = $number - 6; //求到周一差几天
                if($diff_day >= 0 ){
                    $diff_day = - (7 + $diff_day) ;
                }
                $deadline = date("Y-m-d 00:00:00",time() - ($diff_day * 60 * 60 * 24));
                $task = new Task();
                $task->title = '制作人视频评分';
                $task->start_at = now()->toDateTimeString();
                $task->end_at = $deadline;
                $task->creator_id = $user->id;
                $task->principal_id = $user->id;
                $task->privacy = 0;

                $user = Auth::guard('api')->user();

                $departmentId = $user->department()->first()->id;
                $taskType = TaskType::where('title', '视频评分')->where('department_id', $departmentId)->first();

                if ($taskType) {
                    $taskTypeId = $taskType->id;
                } else {
                    return $this->response->errorBadRequest('你所在的部门下没有这个类型');
                }

                $task->principal_id = $user->id;
                $task->type_id = $taskTypeId;
                $task->save();
                if(isset($users)){
                    foreach($users as $key => $val){
                        $moduleuser = new ModuleUser;
                        $moduleuser->user_id = $val['user_id'];
                        $moduleuser->moduleable_id = $task->id;
                        $moduleuser->moduleable_type = 'task';
                        $moduleuser->type = 1;  //1  参与人
                        $modeluseradd = $moduleuser->save();
                    }
                }

                //向任务参与人发消息
                try{
                    $authorization = $request->header()['authorization'][0];
                    event(new TaskMessageEvent($task,TaskTriggerPoint::CREATE_VIDEO_TASK,$authorization,$user));
                }catch (Exception $e){
                    Log::error("消息发送失败");
                    Log::error($e);
                }

            //   $task->type = $taskTypeId;
                $reviewquestionnairemodel = new ReviewQuestionnaire;
                $reviewquestionnairemodel->name = '制作人视频评分-视频评分';
                $reviewquestionnairemodel->creator_id = $array['creator_id'];
                $reviewquestionnairemodel->task_id = $task->id;
              //  $now = now()->toDateTimeString();
//                $number = date("w",time());  //当时是周几
//                $number = $number == 0 ? 7 : $number; //如遇周末,将0换成7
//                $diff_day = $number - 6; //求到周一差几天
//                if($diff_day >= 0 ){
//                    $diff_day = - (7 + $diff_day) ;
//                }
//                $deadline = date("Y-m-d 00:00:00",time() - ($diff_day * 60 * 60 * 24));
                $reviewquestionnairemodel->deadline = $deadline;
                $reviewquestionnairemodel->reviewable_id = $production->id;
                $reviewquestionnairemodel->reviewable_type = 'production';
                $reviewquestionnairemodel->auth_type = '2';
                $reviewquestionnaireadd = $reviewquestionnairemodel->save();
                if($reviewquestionnaireadd == true){
                    foreach($users as $key => $val){
                        $reviewuser = new ReviewUser;
                        $reviewuser->user_id = $val['user_id'];
                        $reviewuser->reviewquestionnaire_id = $reviewquestionnairemodel->id;
                        $reviewuseradd = $reviewuser->save();
                    }

                }
            }
            $issues =ReviewItemAnswer::getIssue();
            $answer =ReviewItemAnswer::getAnswer();
            foreach ($issues as $key => $value){
                $issue['title'] = $value['titles'];
                $issue['review_id'] = $reviewquestionnairemodel->id;
                $issue['sort'] = $reviewquestionnaire->questions()->count() + 1;;
                $reviewQuestion = ReviewQuestion::create($issue);
                $reviewQuestionId = $reviewQuestion->id;
                foreach ($answer[$key] as $key1 => $value1) {
                    $arr = array();
                    $arr['title'] = $value1['answer'];
                    $arr['value'] = $value1['value'];
                    $arr['review_question_id'] = $reviewQuestionId;
                    $arr['sort'] = $reviewquestion->items()->count() + 1;
                    $reviewQuestion = ReviewQuestionItem::create($arr);
                }

            }
            // 操作日志
            //创建做品操作日志
            $operate = new OperateEntity([
                'obj' => $production,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
            //为博主添加做品操作日志
            $operate = new OperateEntity([
                'obj' => $blogger,
                'title' => $production->videoname,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::ADD_PRODUCTION,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->created();
    }
    public function productionIndex(Request $request)
    {
        $payload = $request->all();
        $blogger_id = $request->get('blogger_id');
        $array = array();
        if($request->has('blogger_id')){//姓名
            $array[] = ['blogger_id',hashid_decode($blogger_id)];
        }
        $pageSize = $request->get('page_size', config('app.page_size'));

        $producer_id = BloggerProducer::where($array)->createDesc()->get(['producer_id']);
        $stars = Production::whereIn('id',$producer_id)->createDesc()->paginate($pageSize);
        return $this->response->paginator($stars, new ProductionTransformer());
    }
    public function taskBloggerProductionIndex(Request $request,Task $task)
    {
//        $array['task_id'] = $task->id;
//        $array['resourceable_type'] = 'blogger';
//        $isblogger = TaskResource::where($array)->first();
//        if(!isset($isblogger)){
//            $data = ['data'=>''];
//            return $data;
//        }
       // $taskdata = Task::where('id',$task->id)->first();
//        $arr['name'] =$taskdata->title;
//        // $taskdata->title
//        $arr['creator_id'] = $taskdata->creator_id;
//        $arr['deadline'] = $taskdata->end_at;
//        $arr[] = ['create`d_at','=',$taskdata->created_at];
//        $arr1[] = ['created_at','<',$taskdata->created_at];
//        $taskselect = ReviewQuestionnaire::where($arr)->orwhere($arr1)->orderby('created_at','desc')->first();
        $taskselect = ReviewQuestionnaire::where('task_id',$task->id)->first();
        if(!isset($taskselect)){
            $data = ['data'=>''];
            return $data;
        }
        return $this->response->item($taskselect, new ReviewQuestionnaireShowTransformer());
    }

    /**
     * 暂时不用列表了，逻辑要换
     * @param FilterRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function getFilter(FilterRequest $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', config('app.status'));
        $joinSql = FilterJoin::where('table_name', 'bloggers')->first()->join_sql;
        $query = Blogger::selectRaw('DISTINCT(bloggers.id) as ids')->from(DB::raw($joinSql));
        $bloggers = $query->where(function ($query) use ($payload) {
            FilterReportRepository::getTableNameAndCondition($payload,$query);
        });
        $array = [];//查询条件
        //合同
        if($request->has('status')){//姓名
            $array[] = ['bloggers.status',$status];
        }
        if($request->has('name')){//姓名
            $array[] = ['nickname','like','%'.$payload['name'].'%'];
        }

        if($request->has('type')){//类型
            $array[] = ['type_id',hashid_decode($payload['type'])];
        }
        if($request->has('communication_status')){//沟通状态
            $array[] = ['communication_status',$payload['communication_status']];
        }
        // sign_contract_status   签约状态
        $bloggers = $bloggers->where($array)->searchData()->groupBy('bloggers.id')
            ->get();

//                $sql_with_bindings = str_replace_array('?', $bloggers->getBindings(), $bloggers->toSql());
//                dd($sql_with_bindings);
        $bloggers = Blogger::whereIn('bloggers.id', $bloggers)->leftJoin('operate_logs',function($join){
           $join->on('bloggers.id','operate_logs.logable_id')
               ->where('logable_type',ModuleableType::BLOGGER)
               ->where('operate_logs.method','4');
       })->where(function ($query) use ($payload) {
            if(!empty($payload['conditions'])){
                foreach($payload['conditions'] as $k => $v) {
                    $field = $v['field'];
                    $operator = $v['operator'];
                    $value = $v['value'];
                    $type = $v['type'];

                    if ($field == 'operate_logs.created_at' && $type == '2') {
                        //  Blogger::from(DB::raw($bloggers))->where(NOW(),'>', 'SUBDATE(`operate_logs`.`created_at`,INTERVAL -1 day)');
                        $query->whereRaw("NOW() > SUBDATE(operate_logs.created_at,INTERVAL -$value day)");
                    }
                  }
                }

        })->groupBy('bloggers.id')->orderBy('up_time', 'desc')->orderBy('bloggers.created_at', 'desc')->select(['bloggers.id','nickname','platform_id','communication_status','intention','intention_desc','sign_contract_at','bloggers.level',
            'hatch_star_at','bloggers.status','hatch_end_at','producer_id','sign_contract_status','icon','type_id','desc','avatar','creator_id','gender','cooperation_demand','terminate_agreement_at','sign_contract_other',
            'bloggers.updated_at','bloggers.created_at','sign_contract_other_name',DB::raw("max(operate_logs.updated_at) as up_time")])
            ->paginate($pageSize);
//
//                        $sql_with_bindings = str_replace_array('?', $bloggers->getBindings(), $bloggers->toSql());
//        dd($sql_with_bindings);
        return $this->response->paginator($bloggers, new BloggerTransformer());
    }

    public function import(ExcelImportRequest $request)
    {
        DB::beginTransaction();
        try {
            Excel::import(new BloggersImport(), $request->file('file'));
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            $error = $exception->getMessage();
            return $this->response->errorForbidden($error);
           // return $this->response->errorBadRequest('上传文件排版有问题，请严格按照模版格式填写');
        }
        DB::commit();
        return $this->response->created();
    }

    public function export(Request $request)
    {
        $file = '当前博主导出' . date('YmdHis', time()) . '.xlsx';
        return (new BloggersExport($request))->download($file);
    }


    public function bloggerList2(Request $request)
    {
        $payload = $request->all();
        $search_field = [];
        if (isset($payload['conditions'])){
            $search_field = array_column($payload['conditions'],'field');
        }
        $array = [];//查询条件
        //合同
        if($request->has('status')){//姓名
            $array[] = ['bloggers.status',$payload['status']];
        }
        if($request->has('name')){//姓名
            $array[] = ['nickname','like','%'.$payload['name'].'%'];
        }

        if($request->has('type')){//类型
            $array[] = ['type_id',hashid_decode($payload['type'])];
        }
        if($request->has('communication_status')){//沟通状态
            $array[] = ['communication_status',$payload['communication_status']];
        }
        if($request->has('sign_contract_status')){//姓名
            $array[] = ['bloggers.sign_contract_status',$payload['sign_contract_status']];
        }
        $pageSize = $request->get('page_size', config('app.page_size'));
//        DB::connection()->enableQueryLog();
        $bloggers = BloggerRepository::getBloggerList2($search_field)->searchData()
            ->where(function ($query)use ($payload){
                FilterReportRepository::getTableNameAndCondition($payload,$query);
            })->where($array)
            ->orderBy('bloggers.last_follow_up_at','desc')
//            ->offset(10)->limit(10)->get();
            ->paginate($pageSize);
//        return $bloggers->toArray();
//        dd(DB::getQueryLog());
        return $this->response()->paginator($bloggers,new BloggerListTransformer());
    }


    public function getBloggerDetail(Blogger $blogger,BloggerRepository $bloggerRepository)
    {
//        // 操作日志
//        $operate = new OperateEntity([
//            'obj' => $blogger,
//            'title' => null,
//            'start' => null,
//            'end' => null,
//            'method' => OperateLogMethod::LOOK,
//        ]);
//        event(new OperateLogEvent([
//            $operate,
//        ]));
        $user = Auth::guard("api")->user();
        $blogger->powers = $bloggerRepository->getPower($user,$blogger);
        return $this->response()->item($blogger,new BloggerDetailTransformer());
    }
}
