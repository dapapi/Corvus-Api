<?php

namespace App\Http\Controllers;

use App\Events\ClientDataChangeEvent;
use App\Events\OperateLogEvent;
use App\Events\TrailDataChangeEvent;
use App\Exports\TrailsExport;
use App\Http\Requests\Filter\FilterRequest;
use App\Http\Requests\Trail\EditTrailRequest;
use App\Http\Requests\Trail\FilterTrailRequest;
use App\Http\Requests\Trail\RefuseTrailReuqest;
use App\Http\Requests\Trail\SearchTrailRequest;
use App\Http\Requests\Trail\StoreTrailRequest;
use App\Http\Requests\Trail\TypeTrailReuqest;
use App\Http\Requests\Excel\ExcelImportRequest;
use App\Http\Transformers\TrailTransformer;
use App\Imports\TrailsImport;
use App\Models\DataDictionarie;
use App\Models\Department;
use App\Models\FilterJoin;
use App\Models\Industry;
use App\Models\Message;
use App\ModuleableType;
use App\Models\OperateEntity;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Trail;
use App\Models\TrailStar;
use App\OperateLogMethod;
use App\Repositories\DepartmentRepository;
use App\Repositories\FilterReportRepository;
use App\Repositories\MessageRepository;
use App\Repositories\ScopeRepository;
use App\Repositories\TrailStarRepository;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
//use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel;

class TrailController extends Controller
{
    private $departmentRepository;
    public function __construct(DepartmentRepository $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;
    }

    public function index(FilterTrailRequest $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $trails = Trail::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('trails.title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('status') && !is_null($payload['status']) && $payload['status'] <> '3,4')
                $query->where('type', $payload['status']);
            else if($request->has('status') && $payload['status'] == '3,4'){
                $query->whereIn('type', [3,4]);
            }
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }
            if($request->has('type') && $payload['type'])
                $query->where('type',$payload['type']);

        })
            ->searchData()->poolType()
            //->orderBy('created_at', 'desc')
            ->leftJoin('operate_logs',function($join){
                $join->on('trails.id','operate_logs.logable_id')
                    ->where('logable_type',ModuleableType::TRAIL)
                    ->where('operate_logs.method','4');
            })->groupBy('trails.id')
            ->orderBy('up_time', 'desc')->orderBy('trails.created_at', 'desc')->select(['trails.id','trails.title','brand','principal_id','industry_id','client_id','contact_id','creator_id',
                'type','trails.status','priority','cooperation_type','lock_status','lock_user','lock_at','progress_status','resource','resource_type','take_type','pool_type','receive','fee','desc',
                'trails.updated_at','trails.created_at','take_type','receive',DB::raw("max(operate_logs.updated_at) as up_time")])
            ->paginate($pageSize);
//        $sql_with_bindings = str_replace_array('?', $trails->getBindings(), $trails->toSql());
//        dd($sql_with_bindings);
        return $this->response->paginator($trails, new TrailTransformer());
    }

    public function all(Request $request)
    {
        $type = $request->get('type', '1,2,3,4,5');
        $typeArr = explode(',', $type);
        $trails = Trail::searchData()->whereIn('type', $typeArr)->orderBy('created_at', 'desc')
            ->poolType()->confirmed()
//        $sql_with_bindings = str_replace_array('?', $trails->getBindings(), $trails->toSql());
//        dd($sql_with_bindings);
            ->get();
        return $this->response->collection($trails, new TrailTransformer());
    }
    // todo 根据所属公司存不同类型 去完善 /users/my 目前为前端传type，之前去确认是否改
    public function store(StoreTrailRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        if ($request->has('lock') && $payload['lock'])
            $payload['lock_status'] = 1;

        $payload['principal_id'] = $request->has('principal_id') ? hashid_decode($payload['principal_id']) : null;
        // 改为直接新建
        $payload['contact_id'] = $request->has('contact_id') ? hashid_decode($payload['contact_id']) : null;
        $payload['industry_id'] = hashid_decode($payload['industry_id']);

        if (array_key_exists('id', $payload['contact'])) {

            $contact = Contact::find(hashid_decode($payload['contact']['id']));
            if (!$contact)
                return $this->response->errorBadRequest('联系人不存在');
        } else {
            $contact = null;
        }

        if (array_key_exists('id', $payload['client'])) {
            $client = Client::find(hashid_decode($payload['client']['id']));
            if (!$client)
                return $this->response->errorBadRequest('客户不存在');
        } elseif (array_key_exists('id', $payload['contact'])) {
            return $this->response->errorBadRequest('新建客户不应选现有联系人');
        } else {
            $client = null;
        }

        $user = User::find($payload['principal_id']);
        if (!$user)
            return $this->response->errorBadRequest('用户不存在');

        DB::beginTransaction();

        try {
            if (!array_key_exists('id', $payload['client'])) {
                $client = Client::create([
                    'company' => $payload['client']['company'],
                    'grade' => $payload['client']['grade'],
                    'principal_id' => $payload['principal_id'],
                    'type' => $payload['type'],
                    'creator_id' => $user->id,
                ]);
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $client,
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::CREATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }

            if (!array_key_exists('id', $payload['contact'])) {
                    $dataArray = [];
                    $dataArray['client_id'] = $client->id;
                    $dataArray['name'] = $payload['contact']['name'];
                if($request->has("contact.phone")){
                    $dataArray['phone'] = $payload['contact']['phone'];
                }
                if($request->has("contact.wechat")){
                    $dataArray['wechat'] = $payload['contact']['wechat'];
                }
                if($request->has("contact.other_contact_ways")){
                    $dataArray['other_contact_ways'] = $payload['contact']['other_contact_ways'];
                }
                    $contact = Contact::create($dataArray);

                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $client,
                    'title' => '该用户',
                    'start' => '联系人',
                    'end' => null,
                    'method' => OperateLogMethod::ADD_PERSON,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }

            $payload['contact_id'] = $contact->id;
            $payload['client_id'] = $client->id;

            $trail = Trail::create($payload);

            if ($request->has('expectations') && is_array($payload['expectations'])) {
                (new TrailStarRepository())->store($trail,$payload['expectations'],TrailStar::EXPECTATION);
//                if ($trail->type == Trail::TYPE_PAPI) {
//                    $starableType = ModuleableType::BLOGGER;
//                } else {
//                    $starableType = ModuleableType::STAR;
//                }
//                foreach ($payload['expectations'] as $expectation) {
//                    $starId = hashid_decode($expectation);
//
//                    if ($starableType == ModuleableType::BLOGGER) {
//                        if (Blogger::find($starId))
//                            TrailStar::create([
//                                'trail_id' => $trail->id,
//                                'starable_id' => $starId,
//                                'starable_type' => $starableType,
//                                'type' => TrailStar::EXPECTATION,
//                            ]);
//                    } else {
//                        if (Star::find($starId))
//                            TrailStar::create([
//                                'trail_id' => $trail->id,
//                                'starable_id' => $starId,
//                                'starable_type' => $starableType,
//                                'type' => TrailStar::EXPECTATION,
//                            ]);
//                    }
//                }
            }

            if ($request->has('recommendations') && is_array($payload['recommendations'])) {
                (new TrailStarRepository())->store($trail,$payload['recommendations'],TrailStar::RECOMMENDATION);
//                if ($trail->type == Trail::TYPE_PAPI) {
//                    $starableType = ModuleableType::BLOGGER;
//                } else {
//                    $starableType = ModuleableType::STAR;
//                }
//                foreach ($payload['recommendations'] as $recommendation) {
//                    $starId = hashid_decode($recommendation);
//                    if ($starableType == ModuleableType::BLOGGER) {
//                        if (Blogger::find($starId))
//                            TrailStar::create([
//                                'trail_id' => $trail->id,
//                                'starable_id' => $starId,
//                                'starable_type' => $starableType,
//                                'type' => TrailStar::RECOMMENDATION,
//                            ]);
//                    } else {
//                        if (Star::find($starId))
//                            TrailStar::create([
//                                'trail_id' => $trail->id,
//                                'starable_id' => $starId,
//                                'starable_type' => $starableType,
//                                'type' => TrailStar::RECOMMENDATION,
//                            ]);
//                    }
//                }
            }

            // 操作日志
            $operate = new OperateEntity([
                'obj' => $trail,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('创建线索失败');
        }

        DB::commit();
        //发消息
        if($trail->lock_status == 1){
            DB::beginTransaction();
            try {
                $user = Auth::guard('api')->user();


                // 张峪铭 2019-01-24 20:29  增加锁价人和锁价时间2个字段
                $lock_user = $user->id;
                $lock_at = now()->toDateTimeString();
                $trail_id =$trail->id;
                $data = array();
                $data['lock_user'] = $lock_user;
                $data['lock_at'] = $lock_at;
                Trail::where('id',$trail_id)->update($data);
                // 张峪铭 2019-01-24 20:29  增加锁价人和锁价时间两个字段



                $title = $trail->title." 锁价金额为".$payload['fee'].'元';  //通知消息的标题
                $subheading = $trail->title." 锁价金额为".$payload['fee'].'元';
                $module = Message::TRAILS;
                $link = URL::action("TrailController@detail", ["trail" => $trail->id]);
                $data = [];
                $data[] = [
                    "title" => '线索名称', //通知消息中的消息内容标题
                    'value' => $trail->title,  //通知消息内容对应的值
                ];
                $data[] = [
                    'title' => '预计订单费用',
                    'value' => $payload['fee'],
                ];
            $authorization = $request->header()['authorization'][0];
            $send_to = $this->departmentRepository->getUsersByDepartmentId(Department::BUSINESS_DEPARTMENT);
            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $send_to,$trail->id);
                DB::commit();
            } catch (Exception $e) {
                Log::error($e);
                DB::rollBack();
            }
        }
        return $this->response->item($trail, new TrailTransformer());
    }
    //todo 操作日志怎么记
    public function edit(EditTrailRequest $request, Trail $trail)
    {
        $old_trail = clone $trail;
        $client = $trail->client;
        $old_client = clone $client;
        $payload = $request->all();
        $array = [];
        $arrayOperateLog = [];
        $user = Auth::guard('api')->user();
        if($request->has('title') && !is_null($payload['title'])){//销售线索名称
            $array['title'] = $payload['title'];
            if($payload['title'] != $trail->title){
//                $operateName = new OperateEntity([
//                    'obj' => $trail,
//                    'title' => '销售线索名称',
//                    'start' => $trail->title,
//                    'end' => $payload['title'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['title']);
            }
        }
        if($request->has('resource_type') && !is_null($payload['resource_type'])){//线索来源类型
            //现在的销售线索来源类型
            $start = null;
            $end = null;
            $array['resource_type'] = $payload['resource_type'];
            $curr_resource_type = DataDictionarie::where('parent_id',DataDictionarie::RESOURCE_TYPE)->where('val',$trail->resource_type)->first();
            if($curr_resource_type != null){
                $start = $curr_resource_type->name;
            }
            $resource_type = DataDictionarie::where('parent_id',DataDictionarie::RESOURCE_TYPE)->where('val',$payload['resource_type'])->first();
            if($resource_type == null){
                return $this->response->errorBadRequest("线索来源类型错误");
            }
            $end = $resource_type->name;
            if($payload['resource_type'] != $trail->resource_type){
//                $operateName = new OperateEntity([
//                    'obj' => $trail,
//                    'title' => '销售线索来源类型',
//                    'start' => $start,
//                    'end' => $end,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['resource_type']);
            }
        }
        if($request->has('resource') && !is_null($payload['resource'])){//线索来源
            $array['resource'] = $payload['resource'];
            if($payload['resource'] != $trail->resource){
                try{
                    $start = $trail->resource;
                    if($trail->resource_type == 4){
                        $start = User::find(hashid_decode($trail->resource))->name;
                    }
                    $end = $payload['resource'];
                    if($payload['resource_type'] == 4){//销售线索来源是员工
                        $end = User::find(hashid_decode($payload['resource']))->name;
                    }

//                    $operateName = new OperateEntity([
//                        'obj' => $trail,
//                        'title' => '销售线索来源',
//                        'start' => $start,
//                        'end' => $end,
//                        'method' => OperateLogMethod::UPDATE,
//                    ]);
//                    $arrayOperateLog[] = $operateName;
                }catch (\Exception $e){
                    Log::error($e);
                    return $this->response->errorBadRequest("销售线索来源错误");
                }

            }else{
                unset($array['resource']);
            }
        }

        if ($request->has('principal_id') && !is_null($payload['principal_id'])) {//负责人
            $payload['principal_id'] = hashid_decode($payload['principal_id']);
            $array['principal_id'] = $payload['principal_id'];
            if($payload['principal_id'] != $trail->principal_id){
                try{
//                    $curr_principal = User::find($trail->principal_id);
//                    $principal = User::findOrFail($array['principal_id']);
//                    $operateName = new OperateEntity([
//                        'obj' => $trail,
//                        'title' => '负责人',
//                        'start' => $curr_principal->name,
//                        'end' => $principal->name,
//                        'method' => OperateLogMethod::UPDATE,
//                    ]);
//                    $arrayOperateLog[] = $operateName;
                }catch (\Exception $e){
                    Log::error($e);
                    return $this->response->errorBadRequest("负责人错误");
                }

            }else{
                unset($array['principal_id']);
            }

        }

        if ($request->has('industry_id') && !is_null($payload['industry_id'])) {//行业
            $payload['industry_id'] = hashid_decode($payload['industry_id']);
            $array['industry_id'] = $payload['industry_id'];
            $start = null;
            $end = null;
            if($payload['industry_id'] != $trail->industry_id){
                try{
                    //获取当前的行业名称
                    $curr_industry = Industry::find($payload['industry_id']);
                    if($curr_industry != null){
                        $start = $curr_industry->name;
                    }
                    //要修改的行业
//                    $industry = Industry::findOrFail($payload['industry_id']);
//                    $end = $industry->name;
//                    $operateName = new OperateEntity([
//                        'obj' => $trail,
//                        'title' => '行业',
//                        'start' => $start,
//                        'end' => $end,
//                        'method' => OperateLogMethod::UPDATE,
//                    ]);
//                    $arrayOperateLog[] = $operateName;
                }catch (\Exception $e){
                    return $this->response->errorBadRequest("行业信息错误");
                }

            }else{
                unset($array['industry_id']);
            }

        }

        if ($request->has('fee') && !is_null($payload['fee'])) {//预计订单收入
            $array['fee'] = $payload['fee'];
            if($trail->fee != $payload['fee']){
//                $operateName = new OperateEntity([
//                    'obj' => $trail,
//                    'title' => '预计订单收入',
//                    'start' => $trail->fee,
//                    'end' => $payload['fee'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['fee']);
            }
        }
        if ($request->has('priority') && !is_null($payload['priority'])) {//优先级
            $array['priority'] = $payload['priority'];
            if($trail->priority != $payload['priority']){
//                $operateName = new OperateEntity([
//                    'obj' => $trail,
//                    'title' => '优先级',
//                    'start' => $trail->getPriority($trail->priority),
//                    'end' => $trail->getPriority($payload['priority']),
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['priority']);
            }
        }

        if ($request->has('cooperation_type') && $payload['cooperation_type']) {//合作类型
            $array['cooperation_type'] = $payload['cooperation_type'];
            if($payload['cooperation_type'] != $trail->cooperation_type){
//                $curr_cooperation_type = (new DataDictionarie())->getName(DataDictionarie::COOPERATION_TYPE,$trail->cooperation_type);
                $cooperation_type =  (new DataDictionarie())->getName(DataDictionarie::COOPERATION_TYPE,$trail->cooperation_type);
                if($cooperation_type == null){
                    return $this->response->errorBadRequest("合作类型错误");
                }
//                $operateName = new OperateEntity([
//                    'obj' => $trail,
//                    'title' => '合作类型',
//                    'start' => $curr_cooperation_type,
//                    'end' => $cooperation_type,
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['cooperation_type']);
            }

        }
        if ($request->has('brand') && !is_null($payload['brand'])) {//品牌名称
            $array['brand'] = $payload['brand'];
            if($trail->brand != $payload['brand']){
//                $operateName = new OperateEntity([
//                    'obj' => $trail,
//                    'title' => '品牌',
//                    'start' => $trail->brand,
//                    'end' => $payload['brand'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['brand']);
            }
        }
        if ($request->has('desc') && !is_null($payload['desc'])){//备注
            $array['desc'] = $payload['desc'];
            if($trail->desc != $payload['desc']){
//                $operateName = new OperateEntity([
//                    'obj' => $trail,
//                    'title' => '备注',
//                    'start' => $trail->desc,
//                    'end' => $payload['desc'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['desc']);
            }
        }
        DB::beginTransaction();
        try {
            if ($request->has('lock')) {//操作锁价
                $array['lock_status'] = $payload['lock'];
                if($trail->lock_status != $array['lock_status']){


                    // 张峪铭 2019-01-24 20:29  增加锁价人和锁价时间2个字段
                    $lock_user = $user->id;
                    $lock_at = now()->toDateTimeString();
                    $trail_id =$trail->id;
                    $data = array();
                    $data['lock_user'] = $lock_user;
                    $data['lock_at'] = $lock_at;
                    Trail::where('id',$trail_id)->update($data);
                    // 张峪铭 2019-01-24 20:29  增加锁价人和锁价时间两个字段

//                    $operateName = new OperateEntity([
//                        'obj' => $trail,
//                        'title' => '锁价',
//                        'start' => $trail->lock_status == 1?"锁价":"未锁价",
//                        'end' => $array['lock_status'] == 1?"锁价":"未锁价",
//                        'method' => OperateLogMethod::UPDATE,
//                    ]);
//                    $arrayOperateLog[] = $operateName;
                }else{
                    unset($array['lock_status']);

                }
            }
            $trail->update($payload);
            if ($request->has('client')) {
                if (isset($payload['client']['company'] )){
                    if($payload['client']['company'] != $client->company){//公司名称
//                        $operateName = new OperateEntity([
//                            'obj' => $trail,
//                            'title' => '公司名称',
//                            'start' => $client->company,
//                            'end' => $payload['client']['company'],
//                            'method' => OperateLogMethod::UPDATE,
//                        ]);
//                        $arrayOperateLog[] = $operateName;
                        $client->update($payload['client']);
                    }
                }
                if(isset($payload['client']['grade'])){
                    if($payload['client']['grade'] != $client->grade){//公司级别
//                        $operateName = new OperateEntity([
//                            'obj' => $trail,
//                            'title' => '客户级别',
//                            'start' => $client->grade,
//                            'end' => $payload['client']['grade'],
//                            'method' => OperateLogMethod::UPDATE,
//                        ]);
//                        $arrayOperateLog[] = $operateName;
                        $client->update($payload['client']);
                    }
                }


            }

            if ($request->has('contact')) {//联系人
                $contact = $trail->contact;
                if(isset($payload['contact']['name'])){
                    if($payload['contact']['name'] != $contact->name){
                        $operateName = new OperateEntity([
                            'obj' => $trail,
                            'title' => '联系人',
                            'start' => $contact->name,
                            'end' => $payload['contact']['name'],
                            'method' => OperateLogMethod::UPDATE,
                        ]);
                        $arrayOperateLog[] = $operateName;
                        $contact->update($payload['contact']);
                    }
                }
                if (isset($payload['contact']['phone'])){
                    if($payload['contact']['phone'] != $contact->name){
                        $operateName = new OperateEntity([
                            'obj' => $trail,
                            'title' => '联系人电话',
                            'start' => $contact->phone,
                            'end' => $payload['contact']['phone'],
                            'method' => OperateLogMethod::UPDATE,
                        ]);
                        $arrayOperateLog[] = $operateName;
                        $contact->update($payload['contact']);
                    }
                }
            }

            if ($request->has('expectations') && is_array($payload['expectations'])) {
                try{
                    $repository = new TrailStarRepository();
                    //获取现在关联的艺人和博主
                    $start = $repository->getStarListByTrailId($trail->id,TrailStar::EXPECTATION);
                    $repository->deleteTrailStar($trail->id,TrailStar::EXPECTATION);
                    $repository->store($trail,$payload['expectations'],TrailStar::EXPECTATION);
                    //获取更新之后的艺人和博主列表
                    $end = $repository->getStarListByTrailId($trail->id,TrailStar::EXPECTATION);
//                    $start = null;
//                    $end = null;
//                    if ($trail->type == Trail::TYPE_PAPI) {
//                        $starableType = ModuleableType::BLOGGER;
//                        //获取当前的博主
//                        $blogger_list = $trail->bloggerExpectations()->get()->toArray();
//                        if(count($blogger_list)!=0){
//                            $bloggers = array_column($blogger_list,'nickname');
//                            $start = implode(",",$bloggers);
//                        }
//                    } else {
//                        $starableType = ModuleableType::STAR;
//                        //获取当前的艺人
//                        $star_list = $trail->expectations()->get()->toArray();
//                        if(count($star_list)!=0){
//                            $stars = array_column($star_list,'name');
//                            $start = implode(",",$stars);
//                        }
//
//                    }
//                    //删除之前的目标艺人或者博主
//                    TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::EXPECTATION)->delete();
//                    foreach ($payload['expectations'] as $expectation) {
//                        $starId = hashid_decode($expectation);
//                        if ($starableType == ModuleableType::BLOGGER) {
//                            if ($blogger = Blogger::find($starId))
//                                $end .= ",".$blogger->nickname;
//                                TrailStar::create([
//                                    'trail_id' => $trail->id,
//                                    'starable_id' => $starId,
//                                    'starable_type' => $starableType,
//                                    'type' => TrailStar::EXPECTATION,
//                                ]);
//                        } else {
//                            if ($star = Star::find($starId))
//                                $end .= ",".$star->name;
//                            TrailStar::create([
//                                'trail_id' => $trail->id,
//                                'starable_id' => $starId,
//                                'starable_type' => $starableType,
//                                'type' => TrailStar::EXPECTATION,
//                            ]);
//                        }
//                    }
//                    if($starableType == ModuleableType::BLOGGER){
//                        $title = "关联目标博主";
//                    }else{
//                        $title = "关联目标艺人";
//                    }
                    if (!empty($start) || !empty($end)){
                        $operateName = new OperateEntity([
                            'obj' => $trail,
                            'title' => "关联目标艺人",
                            'start' => $start,
                            'end' => trim($end,","),
                            'method' => OperateLogMethod::UPDATE,
                        ]);
                    }

                    $arrayOperateLog[] = $operateName;
                }catch (\Exception $e){
                    Log::error($e);
                    return $this->response->errorInternal("目标艺人关联失败");
                }


            }

            if ($request->has('recommendations') && is_array($payload['recommendations'])) {
                try{
                    $repository = new TrailStarRepository();
                    //获取现在关联的艺人和博主
                    $start = $repository->getStarListByTrailId($trail->id,TrailStar::RECOMMENDATION);
                    $repository->deleteTrailStar($trail->id,TrailStar::RECOMMENDATION);
                    $repository->store($trail,$payload['recommendations'],TrailStar::RECOMMENDATION);
                    //获取更新之后的艺人和博主列表
                    $end = $repository->getStarListByTrailId($trail->id,TrailStar::RECOMMENDATION);
//                    $start = null;
//                    $end = null;
//                    if ($trail->type == Trail::TYPE_PAPI) {
//                        $starableType = ModuleableType::BLOGGER;
//                        //当前关联的博主
//                        $blogger_list = $trail->bloggerRecommendations()->get()->toArray();
//                        $bloggers = array_column($blogger_list,'nickname');
//                        $start = implode(",",$bloggers);
//                    } else {
//                        $starableType = ModuleableType::STAR;
//                        $star_list = $trail->recommendations()->get()->toArray();
//                        $stars = array_column($star_list,'name');
//                        $start = implode(",",$stars);
//                    }
//                    //删除
//                    TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::RECOMMENDATION)->delete();
//                    foreach ($payload['recommendations'] as $recommendation) {
//                        $starId = hashid_decode($recommendation);
//
//                        if ($starableType == ModuleableType::BLOGGER) {
//                            if ($blogger = Blogger::find($starId))
//                                $end .= $blogger->nickname;
//                                TrailStar::create([
//                                    'trail_id' => $trail->id,
//                                    'starable_id' => $starId,
//                                    'starable_type' => $starableType,
//                                    'type' => TrailStar::RECOMMENDATION,
//                                ]);
//                        } else {
//                            if ($star = Star::find($starId))
//                                $end .= $star->name;
//                                TrailStar::create([
//                                    'trail_id' => $trail->id,
//                                    'starable_id' => $starId,
//                                    'starable_type' => $starableType,
//                                    'type' => TrailStar::RECOMMENDATION,
//                                ]);
//                        }
//                    }
//
//                    if($starableType == ModuleableType::BLOGGER){
//                        $title = "关联推荐博主";
//                    }else{
//                        $title = "关联推荐艺人";
//                    }
                    if (!empty($start) || !empty($end)){
                        $operateName = new OperateEntity([
                            'obj' => $trail,
                            'title' => "关联推荐艺人",
                            'start' => $start,
                            'end' => trim($end,","),
                            'method' => OperateLogMethod::UPDATE,
                        ]);
                        $arrayOperateLog[] = $operateName;
                    }



                }catch (\Exception $e){
                    Log::error($e);
                    return $this->response->errorInternal("推荐艺人关联失败");
                }
            }
            event(new OperateLogEvent($arrayOperateLog));//关联销售线索的客户和联系人日志
            event(new TrailDataChangeEvent($old_trail,$trail));//销售线索日志
            event(new ClientDataChangeEvent($old_client,$client));//客户日志
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('修改销售线索失败');
        }
        DB::commit();
        //发消息
        if($trail->lock_status == 1){
            DB::beginTransaction();
            try {

                $title = $trail->title." 锁价金额为".$trail->fee.'元';  //通知消息的标题
                $subheading = $trail->title." 锁价金额为".$trail->fee.'元';
                $module = Message::TRAILS;
                $link = URL::action("TrailController@detail", ["trail" => $trail->id]);
                $data = [];
                $data[] = [
                    "title" => $title, //通知消息中的消息内容标题
                    'value' => $trail->title,  //通知消息内容对应的值
                ];
                $data[] = [
                    'title' => '预计订单费用',
                    'value' => $trail->fee,
                ];
            $authorization = $request->header()['authorization'][0];
            $send_to = $this->departmentRepository->getUsersByDepartmentId(Department::BUSINESS_DEPARTMENT);
            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $send_to,$trail->id);
                DB::commit();
            } catch (Exception $e) {
                Log::error($e);
                DB::rollBack();
            }
        }
        return $this->response->accepted();

    }

    public function delete(Request $request, Trail $trail)
    {
        $trail->progress_status = Trail::STATUS_DELETE;
        $trail->save();
        $trail->delete();

        return $this->response->noContent();
    }

    public function recover(Request $request, Trail $trail)
    {
        $trail->restore();
        $trail->progress_status = Trail::STATUS_UNCONFIRMED;
        $trail->save();

        $this->response->item($trail, new TrailTransformer());
    }

    public function detail(Request $request, Trail $trail,ScopeRepository $repository)
    {
        $trail = $trail->searchData()->find($trail->id);

        // 操作日志
        $operate = new OperateEntity([
            'obj' => $trail,
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
            $repository->checkPower("trails/{id}",'put',$role_list,$trail);
            $trail->power = "true";
        }catch (Exception $exception){
            $trail->power = "false";
        }
        return $this->response->item($trail, new TrailTransformer());
    }

    public function forceDelete(Request $request, $trail)
    {
        $trail->forceDelete();

        return $this->response->noContent();
    }

    public function search(SearchTrailRequest $request)
    {
        $type = $request->get('type');
        $id = hashid_decode($request->get('id'));

        $pageSize = $request->get('page_size', config('app.page_size'));

        switch ($type) {
            case 'clients':
                $trails = Trail::where('client_id', $id)
                    ->searchData()->poolType()
                    ->paginate($pageSize);
                break;
            default:
                return $this->response->noContent();
                break;
        }

        return $this->response->paginator($trails, new TrailTransformer());
    }

    public function type(TypeTrailReuqest $reuqest)
    {
        $type = $reuqest->get('type');

        $trails = Trail::where('type', $type)
            ->searchData()->poolType()
            ->get();

        return $this->response->collection($trails, new TrailTransformer());
    }

    public function refuse(RefuseTrailReuqest $request, Trail $trail)
    {
//        $power = (new ScopeRepository())->checkMangePower($trail->creator_id, $trail->principal_id, []);
//        if (!$power) {
//            return $this->response->errorInternal("你没有更改线索状态的权限");
//        }
        $type = $request->get('type');
        $reason = $request->get('reason');

        DB::beginTransaction();
        try {
            $operate = new OperateEntity([
                'obj' => $trail,
                'title' => null,
                'start' => $type . '，' . $reason,
                'end' => null,
                'method' => OperateLogMethod::REFUSE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));

            if ($type == '我方拒绝') {
                $status = 2;
            } elseif ($type == '客户拒绝') {
                $status = 3;
            } else {
                throw new \Exception('拒绝类型错误');
            }
            $trail->update([
                'progress_status' => Trail::STATUS_REFUSE,
                'status' => $status
            ]);

        } catch (\Exception $exception) {
            Log::error($exception);
            Db::rollBack();
            return $this->response->errorInternal($exception->getMessage());
        }
        DB::commit();

//        return $this->response->accepted(null, '线索已拒绝');
        return $this->response->accepted();
    }

    public function filter(FilterTrailRequest $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $trails = Trail::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('status') && !is_null($payload['status']) && $payload['status'] <> '3,4')
                $query->where('type', $payload['status']);
            else if($request->has('status') && $payload['status'] == '3,4'){
                $query->whereIn('type', [3,4]);
            }
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }
        })->searchData()->poolType()
            ->leftJoin('operate_logs',function($join){
                $join->on('trails.id','operate_logs.logable_id')
                    ->where('logable_type',ModuleableType::TRAIL)
                    ->where('operate_logs.method','4');
            })->groupBy('trails.id')
            ->orderBy('up_time', 'desc')->orderBy('trails.created_at', 'desc')->select(['trails.id','trails.title','brand','principal_id','industry_id','client_id','contact_id','creator_id',
                'type','trails.status','priority','cooperation_type','lock_status','lock_user','lock_at','progress_status','resource','resource_type','take_type','pool_type','receive','fee','desc',
                'trails.updated_at','trails.created_at','take_type','receive',DB::raw("max(operate_logs.updated_at) as up_time")])
            ->paginate($pageSize);
//        $sql_with_bindings = str_replace_array('?', $trails->getBindings(), $trails->toSql());
//        dd($sql_with_bindings);

        return $this->response->paginator($trails, new TrailTransformer());
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
     *  todo
     *  1. 定返回格式
     *  2. 根据返回拼sql
     *  3. sql返回带分页带eloquent模型
     * @param $request
     */
    public function getFilter(FilterRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $joinSql = FilterJoin::where('table_name', 'trails')->first()->join_sql;
        $query = Trail::selectRaw('DISTINCT(trails.id) as ids')->from(DB::raw($joinSql));
        $trail = $query->where(function ($query) use ($payload) {
            FilterReportRepository::getTableNameAndCondition($payload,$query);
        });
        $trails = $trail->where(function ($query) use ($request, $payload) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('trails.title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('trails.principal_id', $payload['principal_ids']);
            }
            if($request->has('type') && $payload['type'])
                $query->where('trails.type',$payload['type']);
            if ($request->has('status') && !is_null($payload['status']) && $payload['status'] <> '3,4')
                $query->where('trails.type', $payload['status']);
            else if($request->has('status') && $payload['status'] == '3,4'){
                $query->whereIn('trails.type', [3,4]);
            }
        })
            ->searchData()->poolType()->groupBy('trails.id')
            ->get();
//        $sql_with_bindings = str_replace_array('?', $trails->getBindings(), $trails->toSql());
//        dd($sql_with_bindings);
        $trails = Trail::whereIn('trails.id', $trails)->leftJoin('operate_logs',function($join){
                $join->on('trails.id','operate_logs.logable_id')
                    ->where('logable_type',ModuleableType::TRAIL)
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

        })->groupBy('trails.id')->orderBy('up_time', 'desc')->orderBy('trails.created_at', 'desc')->select(['trails.id','trails.title','brand','principal_id','industry_id','client_id','contact_id','creator_id',
                'type','trails.status','priority','cooperation_type','lock_status','lock_user','lock_at','progress_status','resource','resource_type','take_type','pool_type','receive','fee','desc',
                'trails.updated_at','trails.created_at','take_type','receive',DB::raw("max(operate_logs.updated_at) as up_time")])
            ->paginate($pageSize);
//               $sql_with_bindings = str_replace_array('?', $trails->getBindings(), $trails->toSql());
//        dd($sql_with_bindings);
//        $company = $user->company->name;

//        $joinSql = FilterJoin::where('company', $company)->where('table_name', 'trails')->first()->join_sql;

//        $query = DB::table('trails')->selectRaw('DISTINCT(trails.id) as ids')->from(DB::raw($joinSql));

//        $keyword = $request->get('keyword', '');
//        if ($keyword !== '') {
//            // todo 本表中字符型字段模糊查询; 本表中枚举使用的字段也需要加入
//            $query->whereRaw('CONCAT(`trails`.`title`,`trails`.`brand`,`trails`.`desc`) LIKE "%?%"', [$keyword]);
//        }
//        $query = Trail::query();
//        $conditions = $request->get('conditions');
//        foreach ($conditions as $condition) {
//            $field = $condition['field'];
//            $operator = $condition['operator'];
//            $type = $condition['type'];
//            if ($operator == 'LIKE') {
//                $value = '%' . $condition['value'] . '%';
//                $query->whereRaw("$field $operator ?", [$value]);
//            } else if ($operator == 'in') {
//                $value = $condition['value'];
//                if ($type >= 5)
//                    foreach ($value as &$v) {
//                        $v = hashid_decode($v);
//                    }
//                unset($v);
//                $query->whereIn($field, $value);
//            } else {
//                $value = $condition['value'];
//                $query->whereRaw("$field $operator ?", [$value]);
//            }
//
//        }

        // 这句用来检查绑定的参数
 //       $sql_with_bindings = str_replace_array('?', $query->getBindings(), $query->toSql());
//        dd($sql_with_bindings);
 //       $result = $query->pluck('ids')->toArray();

//        $trails = Trail::whereIn('id', $result)->orderBy('created_at', 'desc')->paginate($pageSize);
      //  $trails = $query->searchData()->poolType()->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails, new TrailTransformer());
    }

    public function import(ExcelImportRequest $request)
    {
        DB::beginTransaction();
        try {
            $clientName = $request->file('file') -> getClientOriginalName();
            Excel::import(new TrailsImport($clientName), $request->file('file'));
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

        $file = '当前线索导出' . date('YmdHis', time()) . '.xlsx';
        return (new TrailsExport($request))->download($file);
    }
}
