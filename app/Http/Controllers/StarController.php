<?php

namespace App\Http\Controllers;

use App\CommunicationStatus;
use App\Events\OperateLogEvent;
use App\Gender;
use App\Http\Requests\StarRequest;
use App\Http\Requests\StarUpdateRequest;
use App\Http\Transformers\StarTransformer;
use App\Models\OperateEntity;
use App\Models\Star;
use App\OperateLogMethod;
use App\Repositories\AffixRepository;
use App\SignContractStatus;
use App\StarSource;
use App\User;
use App\Whether;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StarController extends Controller
{
    protected $affixRepository;

    public function __construct(AffixRepository $affixRepository)
    {
        $this->affixRepository = $affixRepository;
    }

    public function index(Request $request)
    {
        $payload = $request->all();
        $array = [];//查询条件
        if($request->has('name')){//姓名
          $array[] = ['name','like','%'.$payload['name'].'%'];
        }
        if($request->has('sign_contract_status')){//签约状态
          $array[] = ['sign_contract_status',$payload['sign_contract_status']];
        }
        if($request->has('communication_status')){//沟通状态
          $array[] = ['communication_status',$payload['communication_status']];
        }
        if($request->has('source')){//艺人来源
          $array[] = ['source',$payload['source']];
        }
        $pageSize = $request->get('page_size', config('app.page_size'));
        $stars = Star::createDesc()
        ->where($array)//根据条件查询
        ->paginate($pageSize);
        return $this->response->paginator($stars, new StarTransformer());
    }

    public function all(Request $request)
    {
        $isAll = $request->get('all', false);
        $stars = Star::createDesc()->get();

        return $this->response->collection($stars, new StarTransformer($isAll));
    }

    public function show(Star $star)
    {
        // 操作日志
        $operate = new OperateEntity([
            'obj' => $star,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
        return $this->response->item($star, new StarTransformer());
    }

    public function recycleBin(Request $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $stars = Star::onlyTrashed()->paginate($pageSize);

        return $this->response->paginator($stars, new StarTransformer());
    }

    public function remove(Star $star)
    {

        DB::beginTransaction();
        try {
            $star->delete();

            // 操作日志
            $operate = new OperateEntity([
                'obj' => $star,
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
        return $this->response->noContent();
    }

    public function recoverRemove(Star $star)
    {

        DB::beginTransaction();
        try {
            $star->restore();
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $star,
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
            return $this->response->errorInternal('恢复艺人失败');
        }
        DB::commit();
    }
    //update
    public function edit(StarUpdateRequest $request, Star $star)
    {
        $payload = $request->all();

        $array = [];
        $arrayOperateLog = [];

        if ($request->has('name')) {
            $array['name'] = $payload['name'];//姓名
            if ($array['name'] != $star->name) {
                $operateName = new OperateEntity([
                    'obj' => $star,
                    'title' => '名称',
                    'start' => $star->name,
                    'end' => $array['name'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
            }
        }

        if ($request->has('gender')) {//性别
            $array['gender'] = $payload['gender'];
            if ($array['gender'] != $star->gender) {

                $start = Gender::getStr($star->gender);
                $end = Gender::getStr($array['gender']);

                $operateGender = new OperateEntity([
                    'obj' => $star,
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

        if ($request->has('avatar')) {//头像
            $array['avatar'] = $payload['avatar'];

            $operateAvatar = new OperateEntity([
                'obj' => $star,
                'title' => '头像',
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::RENEWAL,
            ]);
            $arrayOperateLog[] = $operateAvatar;
        }

        if ($request->has('broker_id')) {//经纪人
            try {
                $start = null;
                if ($star->broker_id) {
                    $currentBroker = User::find($star->broker_id);
                    if ($currentBroker)
                        $start = $currentBroker->name;
                }

                $brokerId = hashid_decode($payload['broker_id']); //经纪人
                $brokerUser = User::findOrFail($brokerId);
                $array['broker_id'] = $brokerId;

                if ($brokerUser->id != $array['broker_id']) {
                    $operateBroker = new OperateEntity([
                        'obj' => $star,
                        'title' => '经纪人',
                        'start' => $start,
                        'end' => $brokerUser->name,
                        'method' => OperateLogMethod::UPDATE,
                    ]);
                    $arrayOperateLog[] = $operateBroker;
                } else {
                    unset($array['broker_id']);
                }
            } catch (Exception $e) {
                return $this->response->errorBadRequest('经纪人错误');
            }
        }

        if ($request->has('birthday')) {//生日
            $array['birthday'] = $payload['birthday'];
            if ($array['birthday'] != $star->birthday) {
                $operateBirthday = new OperateEntity([
                    'obj' => $star,
                    'title' => '生日',
                    'start' => $star->birthday,
                    'end' => $array['birthday'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateBirthday;
            } else {
                unset($array['birthday']);
            }
        }

        if ($request->has('phone')) {//电话
            $array['phone'] = $payload['phone'];
            if ($array['phone'] != $star->phone) {
                $operatePhone = new OperateEntity([
                    'obj' => $star,
                    'title' => '手机号',
                    'start' => $star->phone,
                    'end' => $array['phone'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operatePhone;
            } else {
                unset($array['phone']);
            }
        }

        if ($request->has('desc')) { //描述
            $array['desc'] = $payload['desc'];
            if ($array['desc'] != $star->desc) {
                $operateDesc = new OperateEntity([
                    'obj' => $star,
                    'title' => '描述',
                    'start' => $star->desc,
                    'end' => $array['desc'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateDesc;
            } else {
                unset($array['desc']);
            }
        }

        if ($request->has('wechat')) { //微信
            $array['wechat'] = $payload['wechat'];
            if ($array['wechat'] != $star->wechat) {
                $operateWechat = new OperateEntity([
                    'obj' => $star,
                    'title' => '微信号',
                    'start' => $star->wechat,
                    'end' => $array['wechat'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateWechat;
            } else {
                unset($array['wechat']);
            }
        }

        if ($request->has('email')) {//邮箱
            $array['email'] = $payload['email'];
            if ($array['email'] != $star->email) {
                $operateEmail = new OperateEntity([
                    'obj' => $star,
                    'title' => '邮箱',
                    'start' => $star->email,
                    'end' => $array['email'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateEmail;
            } else {
                unset($array['email']);
            }
        }

        if ($request->has('source')) {//来源
            $array['source'] = $payload['source'];
            if ($array['source'] != $star->source) {

                $start = StarSource::getStr($star->source);
                $end = StarSource::getStr($array['source']);

                $operateSource = new OperateEntity([
                    'obj' => $star,
                    'title' => '艺人来源',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateSource;
            } else {
                unset($array['source']);
            }
        }

        if ($request->has('communication_status')) {//沟通状态
            $array['communication_status'] = $payload['communication_status'];
            if ($array['communication_status'] != $star->communication_status) {

                $start = CommunicationStatus::getStr($star->communication_status);
                $end = CommunicationStatus::getStr($array['communication_status']);

                $operateCommunicationStatus = new OperateEntity([
                    'obj' => $star,
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

        if ($request->has('intention')) {//与我公司签约意向
            $array['intention'] = $payload['intention'];
            if ($array['intention'] != $star->intention) {

                $start = Whether::getStr($star->intention);
                $end = Whether::getStr($array['intention']);

                $operateIntention = new OperateEntity([
                    'obj' => $star,
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

        if ($request->has('intention_desc')) {//不与我公司签约原因
            $array['intention_desc'] = $payload['intention_desc'];
            if ($array['intention_desc'] != $star->intention_desc) {
                $operateIntentionDesc = new OperateEntity([
                    'obj' => $star,
                    'title' => '不与我司签约原因',
                    'start' => $star->intention_desc,
                    'end' => $array['intention_desc'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateIntentionDesc;
            } else {
                unset($array['intention_desc']);
            }
        }

        if ($request->has('sign_contract_other')) {//是否与其他公司签约
            $array['sign_contract_other'] = $payload['sign_contract_other'];
            if ($array['sign_contract_other'] != $star->sign_contract_other) {

                $start = Whether::getStr($star->sign_contract_other);
                $end = Whether::getStr($array['sign_contract_other']);

                $operateSignContractOther = new OperateEntity([
                    'obj' => $star,
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

        if ($request->has('sign_contract_other_name')) {//签约公司名称
            $array['sign_contract_other_name'] = $payload['sign_contract_other_name'];
            if ($array['sign_contract_other_name'] != $star->sign_contract_other_name) {
                $operateSignContractOtherName = new OperateEntity([
                    'obj' => $star,
                    'title' => '签约公司名称',
                    'start' => $star->sign_contract_other_name,
                    'end' => $array['sign_contract_other_name'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateSignContractOtherName;
            } else {
                unset($array['sign_contract_other_name']);
            }
        }

        if ($request->has('sign_contract_at')) {//签约日期
            $array['sign_contract_at'] = $payload['sign_contract_at'];
            if ($array['sign_contract_at'] != $star->sign_contract_at) {
                $operateSignContractAt = new OperateEntity([
                    'obj' => $star,
                    'title' => '签约日期',
                    'start' => $star->sign_contract_at,
                    'end' => $array['sign_contract_at'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateSignContractAt;
            } else {
                unset($array['sign_contract_at']);
            }
        }

        //TODO 此状态只能在合同改变时改变
        /*if ($request->has('sign_contract_status')) {
            $array['sign_contract_status'] = $payload['sign_contract_status'];
            if ($array['sign_contract_status'] != $star->sign_contract_status) {

                $start = SignContractStatus::getStr($star->sign_contract_status);
                $end = SignContractStatus::getStr($array['sign_contract_status']);

                $operateSignContractStatus = new OperateEntity([
                    'obj' => $star,
                    'title' => '签约状态',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateSignContractStatus;
            } else {
                unset($array['sign_contract_status']);
            }
        }*/

        if ($request->has('terminate_agreement_at')) {
            $array['terminate_agreement_at'] = $payload['terminate_agreement_at'];
            if ($array['terminate_agreement_at'] != $star->terminate_agreement_at) {
                $operateTerminateAgreementAt = new OperateEntity([
                    'obj' => $star,
                    'title' => '解约日期',
                    'start' => $star->terminate_agreement_at,
                    'end' => $array['terminate_agreement_at'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateTerminateAgreementAt;
            } else {
                unset($array['terminate_agreement_at']);
            }
        }

        DB::beginTransaction();
        try {
            if (count($array) == 0)
                return $this->response->noContent();

            $star->update($array);
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

    public function store(StarRequest $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();

        unset($payload['status']);
        unset($payload['type']);

        $payload['creator_id'] = $user->id;  //创建者

        if ($request->has('broker_id')) {//经纪人
            try {
                $brokerId = hashid_decode($payload['broker_id']);
                Star::findOrFail($brokerId);
                $payload['broker_id'] = $brokerId;
            } catch (Exception $e) {
                return $this->response->errorBadRequest('经纪人错误');
            }
        }

        DB::beginTransaction();
        try {
            $star = Star::create($payload);//生成艺人对象,并插入数据库
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $star,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));

            if ($request->has('affix') && count($request->get('affix'))) {
                $affixes = $request->get('affix');
                foreach ($affixes as $affix) {
                    try {
                        $this->affixRepository->addAffix($user, $star, $affix['title'], $affix['url'], $affix['size'], $affix['type']);
                        // 操作日志 ...
                    } catch (Exception $e) {
                    }
                }
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->item(Star::find($star->id), new StarTransformer());
    }

    /**
     * 签约
     * @param Request $request
     * @param Star $star
     */
    public function contract(Request $request,Star $star)
    {
        //设置签约状态为已签约
        $star->update([
            'sign_contract_status'=>SignContractStatus::ALREADY_SIGN_CONTRACT,
            'sign_contract_at'  =>  date('Y/m/d H:i:s')
        ]);
        return $this->response->item(Star::find($star->id),new StarTransformer());
    }

    /**解约
     * @param Request $request
     * @param Star $star
     */
    public function terminateAgreement(Request $request,Star $star)
    {
        //设置签约状态为解约
        $star->update([
            'sign_contract_status'=>SignContractStatus::ALREADY_TERMINATE_AGREEMENT,
            'terminate_agreement_at'    =>  date('Y/m/d H:i:s')
        ]);
        return $this->response->item(Star::find($star->id),new StarTransformer());
    }

    //
}
