<?php

namespace App\Http\Controllers;

use App\BloggerLevel;
use App\CommunicationStatus;
use App\Gender;
use App\Http\Requests\BloggerRequest;
use App\Http\Requests\BloggerUpdateRequest;
use App\Http\Requests\BloggerProductionRequest;
use App\Http\Requests\BloggerProducerRequest;
use App\Http\Transformers\BloggerTransformer;
use App\Http\Transformers\BloggerTypeTransformer;
use App\Http\Transformers\BloggerCommunicationTransformer;
use App\Http\Transformers\ProductionTransformer;
use App\Models\Blogger;
use App\Models\Production;
use App\Models\BloggerType;
use App\Models\BloggerCommunication;
use App\Models\BloggerProducer;
use App\Events\OperateLogEvent;
use App\Repositories\OperateLogRepository;
use App\Models\OperateEntity;
use App\Models\Interfaces\OperateLogInterface;
use App\OperateLogMethod;
use App\User;
use App\Whether;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class BloggerController extends Controller
{

    protected $operateLogRepository;

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
        $status = empty($status)?$array[] = ['sign_contract_status',1]:$array[] = ['sign_contract_status',$status];
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
        $bloggers = Blogger::where($array)->createDesc()->paginate($pageSize);
        return $this->response->paginator($bloggers, new BloggerTransformer());
    }
    public function all(Request $request)
    {
        $isAll = $request->get('all', false);
        $bloggers = Blogger::createDesc()->get();
        return $this->response->collection($bloggers, new BloggerTransformer($isAll));
    }
    public function gettypename(Request $request)
    {
        $payload = $request->all();

        $bloggers = BloggerType::get();
        return $this->response->collection($bloggers, new BloggerTypeTransformer());
    }
    public function getcommunication(Request $request)
    {
        $payload = $request->all();
//        $bloggers = Rule::in([
//            CommunicationStatus::ALREADY_SIGN_CONTRACT,
//            CommunicationStatus::HANDLER_COMMUNICATION,
//            CommunicationStatus::TALENT_COMMUNICATION,
//            CommunicationStatus::UNDETERMINED,
//            CommunicationStatus::WEED_OUT,
//            CommunicationStatus::CONTRACT,
//            CommunicationStatus::NO_ANSWER,
//        ]);
//        CommunicationStatus::getStr();
        $bloggers = BloggerCommunication::get();
        return $this->response->collection($bloggers, new BloggerCommunicationTransformer());
    }


    public function show(Blogger $blogger)
    {
        // 操作日志
        $operate = new OperateEntity([
            'obj' => $blogger,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
        return $this->response->item($blogger, new BloggerTransformer());
    }
    public function recycleBin(Request $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $blogger = Blogger::onlyTrashed()->paginate($pageSize);
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
    public function edit(BloggerUpdateRequest $request, Blogger $blogger)
    {
        $payload = $request->all();
        $array = [];
        $arrayOperateLog = [];
        if ($request->has('nickname')) {
            $array['nickname'] = $payload['nickname'];
            if ($array['nickname'] != $blogger->nickname) {
                $operateNickname = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '昵称',
                    'start' => $blogger->nickname,
                    'end' => $array['nickname'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['nickname']);
            }
        }
        if ($request->has('type_id')) {
            try {
                $start = $blogger->type->name;

                $typeId = hashid_decode($payload['type_id']);
                $bloggerType = BloggerType::findOrFail($typeId);
                $end = $bloggerType->name;
                if ($start != $end) {
                    $operateBloggerType = new OperateEntity([
                        'obj' => $blogger,
                        'title' => '类型',
                        'start' => $start,
                        'end' => $end,
                        'method' => OperateLogMethod::UPDATE,
                    ]);
                    $arrayOperateLog[] = $operateBloggerType;
                } else {
                    unset($array['type_id']);
                }
            } catch (Exception $e) {
                return $this->response->errorBadRequest('类型错误');
            }
        }

        if ($request->has('communication_status')) {
            $array['communication_status'] = $payload['communication_status'];
            if ($array['communication_status'] != $blogger->communication_status) {

                $start = CommunicationStatus::getStr($blogger->communication_status);
                $end = CommunicationStatus::getStr($array['communication_status']);

                $operateCommunicationStatus = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '沟通状态',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateCommunicationStatus;
            } else {
                unset($array['communication_status']);
            }
        }

        if ($request->has('intention')) {
            $array['intention'] = $payload['intention'];
            if ($array['intention'] != $blogger->intention) {

                $start = Whether::getStr($blogger->intention);
                $end = Whether::getStr($array['intention']);

                $operateIntention = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '与我司签约意向',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateIntention;
            } else {
                unset($array['intention']);
            }
        }

        if ($request->has('intention_desc')) {
            $array['intention_desc'] = $payload['intention_desc'];
            if ($array['intention_desc'] != $blogger->intention_desc) {
                $operateIntentionDesc = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '不与我司签约原因',
                    'start' => $blogger->intention_desc,
                    'end' => $array['intention_desc'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateIntentionDesc;
            } else {
                unset($array['intention_desc']);
            }
        }

        if ($request->has('sign_contract_at')) {
            $array['sign_contract_at'] = $payload['sign_contract_at'];
            if ($array['sign_contract_at'] != $blogger->sign_contract_at) {
                $operateSignContractAt = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '签约日期',
                    'start' => $blogger->sign_contract_at,
                    'end' => $array['sign_contract_at'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateSignContractAt;
            } else {
                unset($array['sign_contract_at']);
            }
        }


        if ($request->has('level')) {
            $array['level'] = $payload['level'];
            if ($array['level'] != $blogger->level) {

                $start = BloggerLevel::getStr($blogger->level);
                $end = BloggerLevel::getStr($array['level']);

                $operateLevel = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '级别',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateLevel;
            } else {
                unset($array['level']);
            }
        }

        if ($request->has('hatch_star_at')) {
            $array['hatch_star_at'] = $payload['hatch_star_at'];
            if ($array['hatch_star_at'] != $blogger->hatch_star_at) {
                $operateHatchStarAt = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '孵化期开始时间',
                    'start' => $blogger->hatch_star_at,
                    'end' => $array['hatch_star_at'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateHatchStarAt;
            } else {
                unset($array['hatch_star_at']);
            }
        }

        if ($request->has('hatch_end_at')) {
            $array['hatch_end_at'] = $payload['hatch_end_at'];
            if ($array['hatch_end_at'] != $blogger->hatch_end_at) {
                $operateHatchEndAt = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '孵化期结束时间',
                    'start' => $blogger->hatch_end_at,
                    'end' => $array['hatch_end_at'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateHatchEndAt;
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
                    $operateProducer = new OperateEntity([
                        'obj' => $blogger,
                        'title' => '制作人',
                        'start' => $start,
                        'end' => $producerUser->name,
                        'method' => OperateLogMethod::UPDATE,
                    ]);
                    $arrayOperateLog[] = $operateProducer;
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
                $operateDesc = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '描述',
                    'start' => $blogger->desc,
                    'end' => $array['desc'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateDesc;
            } else {
                unset($array['desc']);
            }
        }

        if ($request->has('avatar')) {
            $array['avatar'] = $payload['avatar'];

            $operateAvatar = new OperateEntity([
                'obj' => $blogger,
                'title' => '头像',
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::RENEWAL,
            ]);
            $arrayOperateLog[] = $operateAvatar;
        }

        if ($request->has('gender')) {
            $array['gender'] = $payload['gender'];
            if ($array['gender'] != $blogger->gender) {

                $start = Gender::getStr($blogger->gender);
                $end = Gender::getStr($array['gender']);

                $operateGender = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '性别',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateGender;
            } else {
                unset($array['gender']);
            }
        }

        if ($request->has('cooperation_demand')) {
            $array['cooperation_demand'] = $payload['cooperation_demand'];
            if ($array['cooperation_demand'] != $blogger->cooperation_demand) {
                $operateCooperationDemand = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '合作需求',
                    'start' => $blogger->cooperation_demand,
                    'end' => $array['cooperation_demand'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateCooperationDemand;
            } else {
                unset($array['cooperation_demand']);
            }
        }

        if ($request->has('terminate_agreement_at')) {
            $array['terminate_agreement_at'] = $payload['terminate_agreement_at'];
            if ($array['terminate_agreement_at'] != $blogger->terminate_agreement_at) {
                $operateTerminateAgreementAt = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '解约日期',
                    'start' => $blogger->terminate_agreement_at,
                    'end' => $array['terminate_agreement_at'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateTerminateAgreementAt;
            } else {
                unset($array['terminate_agreement_at']);
            }
        }

        if ($request->has('sign_contract_other')) {
            $array['sign_contract_other'] = $payload['sign_contract_other'];
            if ($array['sign_contract_other'] != $blogger->sign_contract_other) {

                $start = Whether::getStr($blogger->sign_contract_other);
                $end = Whether::getStr($array['sign_contract_other']);

                $operateSignContractOther = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '是否签约其他公司',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateSignContractOther;
            } else {
                unset($array['sign_contract_other']);
            }
        }

        if ($request->has('sign_contract_other_name')) {
            $array['sign_contract_other_name'] = $payload['sign_contract_other_name'];
            if ($array['sign_contract_other_name'] != $blogger->sign_contract_other_name) {
                $operateSignContractOtherName = new OperateEntity([
                    'obj' => $blogger,
                    'title' => '签约公司名称',
                    'start' => $blogger->sign_contract_other_name,
                    'end' => $array['sign_contract_other_name'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateSignContractOtherName;
            } else {
                unset($array['sign_contract_other_name']);
            }
        }

        DB::beginTransaction();
        try {
            if (count($array) == 0)
                return $this->response->noContent();

            $blogger->update($array);
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

    public function store(BloggerRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        $payload['creator_id'] = $user->id;
        if ($request->has('producer_id')) {
            try {
                $producerId = hashid_decode($payload['producer_id']);
                Blogger::findOrFail($producerId);
                $payload['producer_id'] = $producerId;
            } catch (Exception $e) {
                return $this->response->errorBadRequest('制作人错误');
            }
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

    public function productionStore(BloggerProductionRequest $request)
    {
        $payload = $request->all();
        $blooger_id = $payload['blogger_id'];
        unset($payload['blogger_id']);
        DB::beginTransaction();
        try {
            $production = Production::create($payload);
            $model = new BloggerProducer;
            $model->blogger_id =$blooger_id;
            $model->producer_id =$production->id;
            $m = $model->save();
//            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $production,
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
        return $this->response->created();
    }
    public function productionIndex(Request $request)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $stars = Production::createDesc()->paginate($pageSize);

        return $this->response->paginator($stars, new ProductionTransformer());
    }
}
