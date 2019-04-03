<?php

namespace App\Http\Controllers;

use App\AffixType;
use App\CommunicationStatus;
use App\Events\OperateLogEvent;
use App\Events\StarDataChangeEvent;
use App\Gender;
use App\Exports\StarsExport;
use App\Helper\Common;
use App\Http\Requests\Excel\ExcelImportRequest;
use App\Http\Requests\Filter\FilterRequest;
use App\Http\Requests\StarRequest;
use App\Http\Requests\StarUpdateRequest;
use App\Http\Transformers\DashboardModelTransformer;
use App\Http\Transformers\StarAndBloggerTransfromer;
use App\Http\Transformers\StarDeatilTransformer;
use App\Http\Transformers\StarListTransformer;
use App\Http\Transformers\StarTransformer;
use App\Models\Affix;
use App\Models\Blogger;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\FilterJoin;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Star;
use App\Imports\StarsImport;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\Repositories\AffixRepository;
use App\Repositories\FilterReportRepository;
use App\Repositories\ScopeRepository;
use App\Repositories\StarReportRepository;
use App\Repositories\StarRepository;
use App\SignContractStatus;
use App\StarSource;
use App\User;
use App\Whether;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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
        if ($request->has('name')) {//姓名
            $array[] = ['name', 'like', '%' . $payload['name'] . '%'];
        }
        if ($request->has('sign_contract_status') && !empty($payload['sign_contract_status'])) {//签约状态
            $array[] = ['sign_contract_status', $payload['sign_contract_status']];
        }
        if ($request->has('communication_status') && !empty($payload['communication_status'])) {//沟通状态
            $array[] = ['communication_status', $payload['communication_status']];
        }
        if ($request->has('source') && !empty($payload['source'])) {//艺人来源
            $array[] = ['source', $payload['source']];
        }
        $pageSize = $request->get('page_size', config('app.page_size'));
        $stars = Star::where($array)->searchData()->leftJoin('operate_logs', function ($join) {
            $join->on('stars.id', 'operate_logs.logable_id')
                ->where('logable_type', ModuleableType::STAR)
                ->where('operate_logs.method', '4');
        })->groupBy('stars.id')
            ->orderBy('up_time', 'desc')->orderBy('stars.created_at', 'desc')->select(['stars.id','name','broker_id','avatar','gender','birthday','phone','wechat',
                'email','source','communication_status','intention','intention_desc','sign_contract_other','sign_contract_other_name','sign_contract_at','sign_contract_status',
                'terminate_agreement_at','creator_id','stars.status','type','stars.updated_at',
                'platform','stars.created_at',DB::raw("max(operate_logs.updated_at) as up_time")])
        //根据条件查询
//               $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//        dd($sql_with_bindings);
        ->paginate($pageSize);

        return $this->response->paginator($stars, new StarTransformer());
    }

    public function getStarRelated(Request $request)
    {

        $stars = Star::where('sign_contract_status', 2)->searchData()->select('id', 'name')->get();
        $data = array();
        $data['data'] = $stars;
        foreach ($data['data'] as $key => &$value) {
            $value['id'] = hashid_encode($value['id']);
        }
        return $data;

    }

    public function all(Request $request)
    {
        $array = [];//查询条件
        $payload = $request->all();
        if ($request->has('sign_contract_status') && !empty($payload['sign_contract_status'])) {//签约状态
            $array[] = ['sign_contract_status', $payload['sign_contract_status']];
        }
        $isAll = $request->get('all', false);
        $stars = Star::createDesc()->searchData()->where($array)->get();
        return $this->response->collection($stars, new StarTransformer($isAll));
    }

    public function show(Star $star,StarRepository $repository,ScopeRepository $scopeRepository)
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
        $user = Auth::guard("api")->user();
//        //登录用户对艺人编辑权限验证
        try{
            //获取用户角色
            $role_list = $user->roles()->pluck('id')->all();
            $scopeRepository->checkPower("stars/{id}",'put',$role_list,$star);
            $star->power = "true";
        }catch (Exception $exception){
            $star->power = "false";

        }
        $star->powers = $repository->getPower($user,$star);

        //艺人隐私字段
        return $this->response->item($star, new StarTransformer());
    }

    public function recycleBin(Request $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $stars = Star::onlyTrashed()->searchData()->paginate($pageSize);

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

    //编辑艺人，艺人概况
    public function edit(StarUpdateRequest $request, Star $star)
    {
        $payload = $request->all();
        $array = [];
        $arrayOperateLog = [];
        $old_star = clone $star;
        $user = Auth::guard('api')->user();
        if ($request->has('name') && !empty($payload['name'])) {
            $array['name'] = $payload['name'];//姓名
            if ($array['name'] != $star->name) {
//                $operateName = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '名称',
//                    'start' => $star->name,
//                    'end' => $array['name'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }
        }

        if ($request->has('gender')) {//性别
            $array['gender'] = $payload['gender'];
            if ($array['gender'] != $star->gender) {

//                $start = Gender::getStr($star->gender);
//                $end = Gender::getStr($array['gender']);
//
//                $operateGender = new OperateEntity([
//                    'obj' => $star,
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

        if ($request->has('avatar')) {//头像
            $array['avatar'] = $payload['avatar'];

//            $operateAvatar = new OperateEntity([
//                'obj' => $star,
//                'title' => '头像',
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::RENEWAL,
//            ]);
//            $arrayOperateLog[] = $operateAvatar;
        }

        if ($request->has('broker_id')) {//经纪人
            try {
                $start = null;
                if ($star->broker_id) {
                    $currentBroker = User::find($star->broker_id);//现在的经纪人
                    if ($currentBroker)
                        $start = $currentBroker->name;
                }

                $brokerId = hashid_decode($payload['broker_id']); //经纪人
                $brokerUser = User::findOrFail($brokerId);
                $array['broker_id'] = $brokerId;

                if ($brokerUser->id != $currentBroker->id) {
//                    $operateBroker = new OperateEntity([
//                        'obj' => $star,
//                        'title' => '经纪人',
//                        'start' => $start,
//                        'end' => $brokerUser->name,
//                        'method' => OperateLogMethod::UPDATE,
//                    ]);
//                    $arrayOperateLog[] = $operateBroker;
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
//                $operateBirthday = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '生日',
//                    'start' => $star->birthday,
//                    'end' => $array['birthday'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateBirthday;
            } else {
                unset($array['birthday']);
            }
        }

        if ($request->has('phone')) {//电话
            $array['phone'] = $payload['phone'];
            if ($array['phone'] != $star->phone) {
//                $operatePhone = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '手机号',
//                    'start' => $star->phone,
//                    'end' => $array['phone'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operatePhone;
            } else {
                unset($array['phone']);
            }
        }

        if ($request->has('desc')) { //描述
            $array['desc'] = $payload['desc'];
            if ($array['desc'] != $star->desc) {
//                $operateDesc = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '描述',
//                    'start' => $star->desc,
//                    'end' => $array['desc'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateDesc;
            } else {
                unset($array['desc']);
            }
        }

        if ($request->has('wechat')) { //微信
            $array['wechat'] = $payload['wechat'];
            if ($array['wechat'] != $star->wechat) {
//                $operateWechat = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '微信号',
//                    'start' => $star->wechat,
//                    'end' => $array['wechat'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateWechat;
            } else {
                unset($array['wechat']);
            }
        }

        if ($request->has('email')) {//邮箱
            $array['email'] = $payload['email'];
            if ($array['email'] != $star->email) {
//                $operateEmail = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '邮箱',
//                    'start' => $star->email,
//                    'end' => $array['email'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateEmail;
            } else {
                unset($array['email']);
            }
        }

        if ($request->has('source')) {//来源
            $array['source'] = $payload['source'];
            if ($array['source'] != $star->source) {

//                $start = StarSource::getStr($star->source);
//                $end = StarSource::getStr($array['source']);
//
//                $operateSource = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '艺人来源',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateSource;
            } else {
                unset($array['source']);
            }
        }

        if ($request->has('communication_status')) {//沟通状态
            $array['communication_status'] = $payload['communication_status'];
            if ($array['communication_status'] != $star->communication_status) {

//                $start = CommunicationStatus::getStr($star->communication_status);
//                $end = CommunicationStatus::getStr($array['communication_status']);
//
//                $operateCommunicationStatus = new OperateEntity([
//                    'obj' => $star,
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

        if ($request->has('intention')) {//与我公司签约意向
            $array['intention'] = $payload['intention'];
            if ($array['intention'] != $star->intention) {

//                $start = Whether::getStr($star->intention);
//                $end = Whether::getStr($array['intention']);
//
//                $operateIntention = new OperateEntity([
//                    'obj' => $star,
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

        if ($request->has('intention_desc')) {//不与我公司签约原因
            $array['intention_desc'] = $payload['intention_desc'];
            if ($array['intention_desc'] != $star->intention_desc) {
//                $operateIntentionDesc = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '不与我司签约原因',
//                    'start' => $star->intention_desc,
//                    'end' => $array['intention_desc'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateIntentionDesc;
            } else {
                unset($array['intention_desc']);
            }
        }

        if ($request->has('sign_contract_other')) {//是否与其他公司签约
            $array['sign_contract_other'] = $payload['sign_contract_other'];
            if ($array['sign_contract_other'] != $star->sign_contract_other) {

//                $start = Whether::getStr($star->sign_contract_other);
//                $end = Whether::getStr($array['sign_contract_other']);
//
//                $operateSignContractOther = new OperateEntity([
//                    'obj' => $star,
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

        if ($request->has('sign_contract_other_name')) {//签约公司名称
            $array['sign_contract_other_name'] = $payload['sign_contract_other_name'];
            if ($array['sign_contract_other_name'] != $star->sign_contract_other_name) {
//                $operateSignContractOtherName = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '签约公司名称',
//                    'start' => $star->sign_contract_other_name,
//                    'end' => $array['sign_contract_other_name'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateSignContractOtherName;
            } else {
                unset($array['sign_contract_other_name']);
            }
        }

        if ($request->has('sign_contract_at')) {//签约日期
            $array['sign_contract_at'] = $payload['sign_contract_at'];
            if ($array['sign_contract_at'] != $star->sign_contract_at) {
//                $operateSignContractAt = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '签约日期',
//                    'start' => $star->sign_contract_at,
//                    'end' => $array['sign_contract_at'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateSignContractAt;
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
//                $operateTerminateAgreementAt = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '解约日期',
//                    'start' => $star->terminate_agreement_at,
//                    'end' => $array['terminate_agreement_at'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateTerminateAgreementAt;
            } else {
                unset($array['terminate_agreement_at']);
            }
        }
        //社交平台
        if ($request->has('platform') && !empty($payload['platform'])) {
            $array['platform'] = $payload['platform'];
            if ($array['platform'] != $star->platform) {
//                $operatePlatform = new OperateEntity(
//                    [
//                        'obj' => $star,
//                        'title' => '社交平台',
//                        'start' => $star->platform,
//                        'end' => $array['platform'],
//                        'method' => OperateLogMethod::UPDATE,
//                    ]
//                );
//                $arrayOperateLog[] = $operatePlatform;
            }
        }
        //微博url
        if ($request->has('weibo_url')) {
            $array['weibo_url'] = $payload['weibo_url'];
            if ($array['weibo_url'] != $star->weibo_url) {
//                $operateWeiboUrl = new OperateEntity(
//                    [
//                        'obj' => $star,
//                        'title' => '微博主页地址',
//                        'start' => $star->weibo_url,
//                        'end' => $array['weibo_url'],
//                        'method' => OperateLogMethod::UPDATE,
//                    ]
//                );
//                $arrayOperateLog[] = $operateWeiboUrl;
            }
        }
        //微博粉丝数
        if ($request->has('weibo_fans_num')) {
            $array['weibo_fans_num'] = $payload['weibo_fans_num'];
            if ($array['weibo_fans_num'] != $star->weibo_fans_num) {
//                $operateWeiboFansNum = new OperateEntity(
//                    [
//                        'obj' => $star,
//                        'title' => '微博粉丝数',
//                        'start' => $star->weibo_fans_num,
//                        'end' => $array['weibo_fans_num'],
//                        'method' => OperateLogMethod::UPDATE,
//                    ]
//                );
//                $arrayOperateLog[] = $operateWeiboFansNum;
            }
        }
        //抖音id
        if ($request->has('douyin_id')) {
            $array['douyin_id'] = $payload['douyin_id'];
            if ($array['douyin_id'] != $star->douyin_id) {
//                $operateDouyinId = new OperateEntity(
//                    [
//                        'obj' => $star,
//                        'title' => '抖音id',
//                        'start' => $star->douyin_id,
//                        'end' => $array['douyin_id'],
//                        'method' => OperateLogMethod::UPDATE,
//                    ]
//                );
//                $arrayOperateLog[] = $operateDouyinId;
            }
        }
        //抖音粉丝数
        if ($request->has('douyin_fans_num')) {
            $array['douyin_fans_num'] = $payload['douyin_fans_num'];
            if ($array['douyin_fans_num'] != $star->douyin_fans_num) {
//                $operateDouyinFansNum = new OperateEntity(
//                    [
//                        'obj' => $star,
//                        'title' => '抖音粉丝数',
//                        'start' => $star->douyin_fans_num,
//                        'end' => $array['douyin_fans_num'],
//                        'method' => OperateLogMethod::UPDATE,
//                    ]
//                );
//                $arrayOperateLog[] = $operateDouyinFansNum;
            }
        }
        //其他url
        if ($request->has('qita_url')) {
            $array['qita_url'] = $payload['qita_url'];
            if ($array['qita_url'] != $star->qita_url) {
//                $operateQitaUrl = new OperateEntity(
//                    [
//                        'obj' => $star,
//                        'title' => '其他url',
//                        'start' => $star->qita_url,
//                        'end' => $array['qita_url'],
//                        'method' => OperateLogMethod::UPDATE,
//                    ]
//                );
//                $arrayOperateLog[] = $operateQitaUrl;
            }
        }
        //其他粉丝数
        if ($request->has('qita_fans_num')) {
            $array['qita_fans_num'] = $payload['qita_fans_num'];
            if ($array['qita_fans_num'] != $star->qita_fans_num) {
//                $operateQitaFansNum = new OperateEntity(
//                    [
//                        'obj' => $star,
//                        'title' => '其他粉丝数',
//                        'start' => $star->qita_fans_num,
//                        'end' => $array['qita_fans_num'],
//                        'method' => OperateLogMethod::UPDATE,
//                    ]
//                );
//                $arrayOperateLog[] = $operateQitaFansNum;
            }
        }
        //星探
        if ($request->has('artist_scout_name')) {
            $array['artist_scout_name'] = $payload['artist_scout_name'];
            if ($array['artist_scout_name'] != $star->artist_scout_name) {
//                $operateArtistScoutName = new OperateEntity(
//                    [
//                        'obj' => $star,
//                        'title' => '星探',
//                        'start' => $star->artist_scout_name,
//                        'end' => $array['artist_scout_name'],
//                        'method' => OperateLogMethod::UPDATE,
//                    ]
//                );
//                $arrayOperateLog[] = $operateArtistScoutName;
            }
        }
        if ($request->has('star_location')) {
            $array['star_location'] = $payload['star_location'];
            if ($array['star_location'] != $star->star_location) {
//                $operateStarLocation = new OperateEntity(
//                    [
//                        'obj' => $star,
//                        'title' => '星探地区',
//                        'start' => $star->star_location,
//                        'end' => $array['star_location'],
//                        'method' => OperateLogMethod::UPDATE,
//                    ]
//                );
//                $arrayOperateLog[] = $operateStarLocation;
            }
        }
        if ($request->has("star_risk_point")) {
            $array['star_risk_point'] = $payload['star_risk_point'];
        }
        DB::beginTransaction();
        try {
            if ($request->has('affix') && count($request->get('affix'))) {
                $affixes = $request->get('affix');
                foreach ($affixes as $affix) {
                    try {
                        $affixmodel = null;
                        //查找对应类型的附件是否存在
                        $affixmodel = Affix::where([
                            ['type', $affix['type']],
                            ['affixable_type', ModuleableType::STAR],
                            ['affixable_id', $star->id]
                        ])->first();
                        if ($affixmodel) {//存在则删除
                            $affixmodel->delete();
                        }
                        $this->affixRepository->addAffix($user, $star, $affix['title'], $affix['url'], $affix['size'], $affix['type']);
                        // 操作日志 ...
                    } catch (Exception $e) {
                    }
                }
            }
//            if (count($array) == 0)
//                return $this->response->noContent();
            if (count($array) != 0)
                $star->update($array);
            // 操作日志
//            event(new OperateLogEvent($arrayOperateLog));
            event(new StarDataChangeEvent($old_star, $star));//记录日志
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
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
                        Log::error($e);
                    }
                }
            }

        } catch (Exception $e) {
            dd($e);
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
    public function contract(Request $request, Star $star)
    {
        //设置签约状态为已签约
        $star->update([
            'sign_contract_status' => SignContractStatus::ALREADY_SIGN_CONTRACT,
            'sign_contract_at' => date('Y/m/d H:i:s')
        ]);
        return $this->response->item(Star::find($star->id), new StarTransformer());
    }

    /**解约
     * @param Request $request
     * @param Star $star
     */
    public function terminateAgreement(Request $request, Star $star)
    {
        //设置签约状态为解约
        $star->update([
            'sign_contract_status' => SignContractStatus::ALREADY_TERMINATE_AGREEMENT,
            'terminate_agreement_at' => date('Y/m/d H:i:s')
        ]);
        return $this->response->item(Star::find($star->id), new StarTransformer());
    }

    /**
     * 获取粉丝数据
     * @param Request $request
     */
    public function getStarFensi(Request $request)
    {
        $star_id = $request->get('star_id', 'null');
        $star_id = hashid_decode($star_id);
        $starable_type = $request->get('starable_type', null);
        $star_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $report = StarReportRepository::getFensiByStarId($star_id, $starable_type, $star_time, $end_time);
        return $report->platforms();
    }

    /**
     * 获取截止在当前日期之前的5个完成中的项目和任务
     */
    public function getFiveTaskAndProjejct(Request $request, Star $star)
    {
//        $projects = $star->project()
//            ->where('end_at','<',Carbon::now()->toDateString())
//            ->limit(5)->orderBy('created_at','desc')
//            ->get();
//        $tasks = $star->tasks()->where('end_at','<',Carbon::now()->toDateString())->limit(5)->orderBy('created_at','desc')->get();
        return StarReportRepository::getFiveProjectAndTask($star->id);
    }

    //获取艺人和博主的列表
    public function getStarAndBlogger(Request $request)
    {
        $array = [];
        $payload = $request->all();
        if ($request->has('sign_contract_status') && !empty($payload['sign_contract_status'])) {//签约状态
            $array[] = ['sign_contract_status', $payload['sign_contract_status']];
        }
        $first = Star::select('name', 'id', 'sign_contract_status', DB::raw('\'star\''))->searchData()->where($array);
        $stars = Blogger::select('nickname', 'id', 'sign_contract_status',
            DB::raw('\'blogger\' as flag'))
            ->where($array)
            ->searchData()
            ->union($first)
            ->get();


        return $this->response->collection($stars, new StarAndBloggerTransfromer());
    }

    /**
     * 暂时不用列表了，逻辑要换
     * @param FilterRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function getFilter(FilterRequest $request, StarRepository $repository)
    {
        $payload = $request->all();
        $array = [];//查询条件
        if ($request->has('name')) {//姓名
            $array[] = ['stars.name', 'like', '%' . $payload['name'] . '%'];
        }
        if ($request->has('sign_contract_status') && !empty($payload['sign_contract_status'])) {//签约状态
            $array[] = ['stars.sign_contract_status', $payload['sign_contract_status']];
        }
        if ($request->has('communication_status') && !empty($payload['communication_status'])) {//沟通状态
            $array[] = ['stars.communication_status', $payload['communication_status']];
        }
        if ($request->has('source') && !empty($payload['source'])) {//艺人来源
            $array[] = ['stars.source', $payload['source']];
        }
        $pageSize = $request->get('page_size', config('app.page_size'));


        $all = $request->get('all', false);
//        $joinSql = FilterJoin::where('table_name', 'stars')->first()->join_sql;
//        $query = Star::from(DB::raw($joinSql))->select("stars.*");


        $query =    $repository->starCustomSiftBuilder();
//        $query = StarRepository::getStarList();
        $stars = $query->where(function ($query) use ($payload) {
            FilterReportRepository::getTableNameAndCondition($payload, $query);
        });


        $stars->select('stars.id','stars.platform','stars.name','stars.source','stars.created_at','stars.birthday','stars.created_at','stars.updated_at','stars.sign_contract_status',"operate_logs.user_id")
            ->where($array)
            ->searchData();
//        DB::connection()->enableQueryLog();
        $stars = $stars->orderBy('operate_logs.created_at','desc')->groupBy('stars.id')->paginate($pageSize);
//        dd($stars->toArray());
//        dd(DB::getQueryLog());

        return $this->response->paginator($stars, new StarTransformer(!$all));
    }

    public function import(ExcelImportRequest $request)
    {
        DB::beginTransaction();
        try {

            $clientName = $request->file('file')->getClientOriginalName();
            Excel::import(new StarsImport($clientName), $request->file('file'));
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            $error = $exception->getMessage();
            return $this->response->errorForbidden($error);
            //return $this->response->errorBadRequest('上传文件排版有问题，请严格按照模版格式填写');
        }
        DB::commit();
        return $this->response->created();
    }

    public function export(Request $request)
    {
        $file = '当前艺人导出' . date('YmdHis', time()) . '.xlsx';
        return (new StarsExport($request))->download($file);
    }

    public function dashboard(Request $request, Department $department)
    {
        $days = $request->get('days', 7);
        $departmentId = $department->id;
        $departmentArr = Common::getChildDepartment($departmentId);
        $userIds = DepartmentUser::whereIn('department_id', $departmentArr)->pluck('user_id');

        $stars = Star::select('stars.id as id', DB::raw('GREATEST(stars.created_at, COALESCE(MAX(b.created_at), 0)) as t'), 'stars.created_at', 'stars.name as title')
            ->join('module_users', function ($join) {
                $join->on('stars.id', '=', 'module_users.moduleable_id')
                    ->where('module_users.moduleable_type', '=', ModuleableType::STAR)
                    ->where('module_users.type', '=', ModuleUserType::BROKER);
            })
            ->whereIn('module_users.user_id', $userIds)
            ->leftJoin('operate_logs as b', function ($join) {
                $join->on('stars.id', '=', 'b.logable_id')
                    ->where('b.logable_type', '=', ModuleableType::STAR)
                    ->where('b.method', '=', OperateLogMethod::FOLLOW_UP);
            })
            ->groupBy('stars.id')
            ->orderBy('t', 'desc')
            ->take(5)
            ->get();
//        $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//        dd($sql_with_bindings);
        $result = $this->response->collection($stars, new DashboardModelTransformer());

//        dd($result);
        $count = Star::select('stars.id as id')
            ->join('module_users', function ($join) {
                $join->on('stars.id', '=', 'module_users.moduleable_id')
                    ->where('module_users.moduleable_type', '=', ModuleableType::STAR)
                    ->where('module_users.type', '=', ModuleUserType::BROKER);
            })
            ->whereIn('module_users.user_id', $userIds)->distinct('stars.id')->count('stars.id');

        $timePoint = Carbon::today('PRC')->subDays($days);

        $latestFollow = Star::join('module_users', function ($join) {
            $join->on('stars.id', '=', 'module_users.moduleable_id')
                ->where('module_users.moduleable_type', '=', ModuleableType::STAR);
        })
            ->where('module_users.type', '=', ModuleUserType::BROKER)
            ->whereIn('module_users.user_id', $userIds)->join('operate_logs', function ($join) {
                $join->on('stars.id', '=', 'operate_logs.logable_id')
                    ->where('operate_logs.logable_type', ModuleableType::STAR)
                    ->where('operate_logs.method', OperateLogMethod::FOLLOW_UP);
            })->where('operate_logs.created_at', '>', $timePoint)->distinct('stars.id')->count('stars.id');

        $starIdArr = Star::select('stars.id as id', DB::raw('GREATEST(stars.created_at, MAX(b.created_at)) as t'), 'stars.name as title')
            ->join('module_users', function ($join) {
                $join->on('stars.id', '=', 'module_users.moduleable_id')
                    ->where('module_users.moduleable_type', '=', ModuleableType::STAR)
                    ->where('module_users.type', '=', ModuleUserType::BROKER);
            })
            ->whereIn('module_users.user_id', $userIds)
            ->join('operate_logs as b', function ($join) {
                $join->on('stars.id', '=', 'b.logable_id')
                    ->where('b.logable_type', ModuleableType::STAR)
                    ->where('b.method', '=', OperateLogMethod::FOLLOW_UP);
            })->groupBy('stars.id')->pluck('stars.id');

        $withTrail = Trail::leftJoin('trail_star', function ($join) {
            $join->on('trail_star.trail_id', '=', 'trails.id')
                ->where('starable_type', ModuleableType::STAR)
                ->where('trail_star.type', TrailStar::EXPECTATION);
        })->whereIn('starable_id', $starIdArr)->where('trail_star.created_at', '>', $timePoint)->distinct('trails.id')->count('trails.id');
        $trailIdArr = Trail::leftJoin('trail_star', function ($join) {
            $join->on('trail_star.trail_id', '=', 'trails.id')
                ->where('starable_type', ModuleableType::STAR)
                ->where('trail_star.type', TrailStar::EXPECTATION);
        })->whereIn('starable_id', $starIdArr)->where('trail_star.created_at', '>', $timePoint)->distinct('trails.id')->pluck('trails.id');
        $withProject = Project::whereIn('trail_id', $trailIdArr)->where('created_at', '>', $timePoint)->distinct('projects.id')->count('projects.id');

        $starInfoArr = [
            'total' => $count,
            'latest_follow' => $latestFollow,
            'with_trail' => $withTrail,
            'with_project' => $withProject,
        ];

        $result->addMeta('count', $starInfoArr);
        return $result;
    }

    public function getStarList(Request $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $condition = null;
        if (isset($payload['conditions'])){
            $condition = FilterReportRepository::getCondition($payload['conditions']);
        }
        $star_list =  StarRepository::getStarList($condition);
        $res = [];
        foreach ($star_list as $key => $star){
            $temp['id'] = hashid_encode($star->id);
            $temp['contracts']['data']['contract_start_date'] = $star->sign_contract_at;
            $temp['contracts']['data']['contract_end_date'] = $star->terminate_agreement_at;
            $temp['sign_contract_at'] = $star->sign_contract_at;
            $temp['terminate_agreement_at'] = $star->terminate_agreement_at;
            $temp['name'] = $star->name;
            $temp['weibo_fans_num'] = $star->weibo_fans_num;
            $temp['source'] = $star->source;
            $temp['created_at'] = $star->created_at;
            $temp['last_follow_up_at'] = $star->last_follow_up_at;
            $temp['sign_contract_status'] = $star->sign_contract_status;
            $temp['birthday'] = $star->birthday;
            $temp['communication_status'] = $star->communication_status;
            $res[] = $temp;
        }
        $meta = [
            "pagination"=> [
                "total"=> 576,
                "count"=> 15,
                "per_page"=> 15,
                "current_page"=> 1,
                "total_pages"=> 39,
                "links"=> [
                    "next"=> "http://corvus.cn/stars/filter?page=2"
                ],
            ]
        ];
        return [
            "data" => $res,
            "meta"  => $meta,
            "status"    =>  "success"
        ];
    }
    public function getStarById(Star $star)
    {
        return $this->response()->item($star,new StarDeatilTransformer());
    }

    public function getStarList2(Request $request)
    {
        $payload = $request->all();
        $search_field = [];
        if (isset($payload['conditions'])){
            $search_field = array_column($payload['conditions'],'field');
        }
        $array = [];//查询条件
        if ($request->has('name')) {//姓名
            $array[] = ['stars.name', 'like', '%' . $payload['name'] . '%'];
        }
        if ($request->has('sign_contract_status') && !empty($payload['sign_contract_status'])) {//签约状态
            $array[] = ['stars.sign_contract_status', $payload['sign_contract_status']];
        }
        if ($request->has('communication_status') && !empty($payload['communication_status'])) {//沟通状态
            $array[] = ['stars.communication_status', $payload['communication_status']];
        }
        if ($request->has('source') && !empty($payload['source'])) {//艺人来源
            $array[] = ['stars.source', $payload['source']];
        }
        $pageSize = $request->get('page_size', config('app.page_size'));
//        DB::connection()->enableQueryLog();
        $star_list = StarRepository::getStarList2($search_field)->searchData()->where(function ($query) use ($payload) {
            FilterReportRepository::getTableNameAndCondition($payload, $query);
        })->where($array)
            ->paginate($pageSize);
//            ->offset(10)->limit(10);
//        return $star_list;
//        dd(DB::getQueryLog());
//        return DB::select("select stars.id,stars.name,stars.weibo_fans_num,stars.source,stars.sign_contract_status,stars.created_at,stars.last_follow_up_at,stars.sign_contract_at,stars.birthday,stars.terminate_agreement_at,stars.communication_status from stars ");
        return $this->response()->paginator($star_list,new StarListTransformer());
    }
}
