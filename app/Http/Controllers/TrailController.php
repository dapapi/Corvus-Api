<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Exports\TrailsExport;
use App\Http\Requests\Filter\TrailFilterRequest;
use App\Http\Requests\Trail\EditTrailRequest;
use App\Http\Requests\Trail\FilterTrailRequest;
use App\Http\Requests\Trail\RefuseTrailReuqest;
use App\Http\Requests\Trail\SearchTrailRequest;
use App\Http\Requests\Trail\StoreTrailRequest;
use App\Http\Requests\Trail\TypeTrailReuqest;
use App\Http\Transformers\TrailTransformer;
use App\Models\Blogger;
use App\Models\DataDictionarie;
use App\Models\DataDictionary;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\FilterJoin;
use App\Models\Industry;
use App\Models\Message;
use App\Models\OperateEntity;
use App\Models\Star;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\OperateLogMethod;
use App\Repositories\ScopeRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Excel;

class TrailController extends Controller
{
    public function index(FilterTrailRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $department_id = Department::where('name', '商业管理部')->first();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $trails = Trail::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('status') && !is_null($payload['status']))
                $query->where('progress_status', $payload['status']);
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }
        })->searchData()->orderBy('created_at', 'desc')->paginate($pageSize);
//        if($department_id){
//            $department_ids = Department::where('department_pid', $department_id->id)->get(['id']);
//            $user_ids = DepartmentUser::wherein('department_id',$department_ids)->where('user_id',$user->id)->get(['user_id'])->toArray();
//            if(!$user_ids){
//
//            }
//        }
//
//
//
//        dd($trails->get());

//            ->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails, new TrailTransformer());
    }

    public function all(Request $request)
    {
        $type = $request->get('type', '1,2,3,4,5');
        $typeArr = explode(',', $type);
        $clients = Trail::whereIn('type', $typeArr)->orderBy('created_at', 'desc')
            ->searchData()
            ->get();
        return $this->response->collection($clients, new TrailTransformer());
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

        if (is_numeric($payload['resource'])) {
            $payload['resource'] = hashid_decode($payload['resource']);
        }

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
                $contact = Contact::create([
                    'client_id' => $client->id,
                    'name' => $payload['contact']['name'],
                    'phone' => $payload['contact']['phone'],
                ]);
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
                if ($trail->type == Trail::TYPE_PAPI) {
                    $starableType = ModuleableType::BLOGGER;
                } else {
                    $starableType = ModuleableType::STAR;
                }
                foreach ($payload['expectations'] as $expectation) {
                    $starId = hashid_decode($expectation);

                    if ($starableType == ModuleableType::BLOGGER) {
                        if (Blogger::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::EXPECTATION,
                            ]);
                    } else {
                        if (Star::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::EXPECTATION,
                            ]);
                    }
                }
            }

            if ($request->has('recommendations') && is_array($payload['recommendations'])) {
                if ($trail->type == Trail::TYPE_PAPI) {
                    $starableType = ModuleableType::BLOGGER;
                } else {
                    $starableType = ModuleableType::STAR;
                }
                foreach ($payload['recommendations'] as $recommendation) {
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
                $title = $trail->title." 锁价金额为".$payload['fee'].'元';  //通知消息的标题
                $subheading = $trail->title." 锁价金额为".$payload['fee'].'元';
                $module = Message::PROJECT;
                $link = URL::action("TrailController@detail", ["trail" => $trail->id]);
                $data = [];
                $data[] = [
                    "title" => '线索名臣', //通知消息中的消息内容标题
                    'value' => $trail->title,  //通知消息内容对应的值
                ];
                $data[] = [
                    'title' => '预计订单费用',
                    'value' => $payload['fee'],
                ];
                //TODO 发给papi商务组，商务组暂时没建立
//            $participant_ids = isset($payload['participant_ids']) ? $payload['participant_ids'] : null;
//            $authorization = $request->header()['authorization'][0];
//
//            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $participant_ids);
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
        $payload = $request->all();
        $array = [];
        $arrayOperateLog = [];
        if($request->has('title') && !is_null($payload['title'])){//销售线索名称
            $array['title'] = $payload['title'];
            if($payload['title'] != $trail->title){
                $operateName = new OperateEntity([
                    'obj' => $trail,
                    'title' => '销售线索名称',
                    'start' => $trail->title,
                    'end' => $payload['title'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
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
                return $this->response->errorInternal("线索来源类型错误");
            }
            $end = $resource_type->name;
            if($payload['resource_type'] != $trail->resource_type){
                $operateName = new OperateEntity([
                    'obj' => $trail,
                    'title' => '销售线索来源类型',
                    'start' => $start,
                    'end' => $end,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
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
                        $start = User::find($trail->resource)->name;
                    }
                    $end = $payload['resource'];
                    if($payload['resource_type'] == 4){//销售线索来源是员工
                        $end = User::findOrFail(hashid_decode($payload['resource']));
                    }

                    $operateName = new OperateEntity([
                        'obj' => $trail,
                        'title' => '销售线索来源',
                        'start' => $start,
                        'end' => $end,
                        'method' => OperateLogMethod::UPDATE,
                    ]);
                    $arrayOperateLog[] = $operateName;
                }catch (\Exception $e){
                    dd($e);
                    Log::error($e);
                    return $this->response->errorInternal("销售线索来源错误");
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
                    $curr_principal = User::find($trail->principal_id);
                    $principal = User::findOrFail($payload['principal_id']);
                    $operateName = new OperateEntity([
                        'obj' => $trail,
                        'title' => '负责人',
                        'start' => $curr_principal->name,
                        'end' => $principal->name,
                        'method' => OperateLogMethod::UPDATE,
                    ]);
                    $arrayOperateLog[] = $operateName;
                }catch (\Exception $e){
                    return $this->response->errorInternal("负责人错误");
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
                    $industry = Industry::findOrFail($payload['industry_id']);
                    $end = $industry->name;
                    $operateName = new OperateEntity([
                        'obj' => $trail,
                        'title' => '行业',
                        'start' => $start,
                        'end' => $end,
                        'method' => OperateLogMethod::UPDATE,
                    ]);
                    $arrayOperateLog[] = $operateName;
                }catch (\Exception $e){
                    return $this->response->errorInternal("行业信息错误");
                }

            }else{
                unset($array['industry_id']);
            }

        }

        if ($request->has('fee') && !is_null($payload['fee'])) {//预计订单收入
            $array['fee'] = $payload['fee'];
            if($trail->fee != $payload['fee']){
                $operateName = new OperateEntity([
                    'obj' => $trail,
                    'title' => '预计订单收入',
                    'start' => $trail->fee,
                    'end' => $payload['fee'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['fee']);
            }
        }
        if ($request->has('priority') && !is_null($payload['priority'])) {//优先级
            $array['priority'] = $payload['priority'];
            if($trail->priority != $payload['priority']){
                $operateName = new OperateEntity([
                    'obj' => $trail,
                    'title' => '优先级',
                    'start' => $trail->priority,
                    'end' => $payload['priority'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['priority']);
            }
        }

        if ($request->has('cooperation_type') && $payload['cooperation_type']) {//合作类型
            $array['cooperation_type'] = $payload['cooperation_type'];
            if($payload['status'] != $trail->status){
                $curr_cooperation_type = DataDictionarie::getName(DataDictionarie::COOPERATION_TYPE,$trail->cooperation_type);
                $cooperation_type =  DataDictionarie::getName(DataDictionarie::COOPERATION_TYPE,$trail->cooperation_type);
                if($cooperation_type == null){
                    return $this->response->errorInternal("合作类型错误");
                }
                $operateName = new OperateEntity([
                    'obj' => $trail,
                    'title' => '线索状态',
                    'start' => $curr_cooperation_type,
                    'end' => $cooperation_type,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['cooperation_type']);
            }

        }
        if ($request->has('brand') && !is_null($payload['brand'])) {//品牌名称
            $array['brand'] = $payload['brand'];
            if($trail->brand != $payload['brand']){
                $operateName = new OperateEntity([
                    'obj' => $trail,
                    'title' => '优先级',
                    'start' => $trail->brand,
                    'end' => $payload['brand'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['brand']);
            }
        }
        if ($request->has('desc') && !is_null($payload['desc'])){//备注
            $array['desc'] = $payload['desc'];
            if($trail->desc != $payload['desc']){
                $operateName = new OperateEntity([
                    'obj' => $trail,
                    'title' => '备注',
                    'start' => $trail->desc,
                    'end' => $payload['desc'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
            }else{
                unset($array['desc']);
            }
        }


        DB::beginTransaction();
        try {
            if ($request->has('lock')) {//操作锁价


                $array['lock_status'] = $payload['lock'];
                if($trail->lock_status != $array['lock_status']){
                    $operateName = new OperateEntity([
                        'obj' => $trail,
                        'title' => '锁价',
                        'start' => $trail->lock_status == 1?"锁价":"未锁价",
                        'end' => $array['lock_status'] == 1?"锁价":"未锁价",
                        'method' => OperateLogMethod::UPDATE,
                    ]);
                    $arrayOperateLog[] = $operateName;
                }else{
                    unset($array['lock_status']);

                }
            }
            $trail->update($array);
            if ($request->has('client')) {
                $client = $trail->client;
                if (isset($payload['client']['company'] )){
                    if($payload['client']['company'] != $client->company){//公司名称
                        $operateName = new OperateEntity([
                            'obj' => $trail,
                            'title' => '公司名称',
                            'start' => $client->company,
                            'end' => $payload['client']['company'],
                            'method' => OperateLogMethod::UPDATE,
                        ]);
                        $arrayOperateLog[] = $operateName;
                        $client->update($payload['client']);
                    }
                }
                if(isset($payload['client']['grade'])){
                    if($payload['client']['grade'] != $client->grade){//公司级别
                        $operateName = new OperateEntity([
                            'obj' => $trail,
                            'title' => '客户级别',
                            'start' => $client->grade,
                            'end' => $payload['client']['grade'],
                            'method' => OperateLogMethod::UPDATE,
                        ]);
                        $arrayOperateLog[] = $operateName;
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
                    $start = null;
                    $end = null;
                    if ($trail->type == Trail::TYPE_PAPI) {
                        $starableType = ModuleableType::BLOGGER;
                        //获取当前的博主
                        $blogger_list = $trail->bloggerExpectations()->get()->toArray();
                        if(count($blogger_list)!=0){
                            $bloggers = array_column($blogger_list,'nickname');
                            $start = implode(",",$bloggers);
                        }
                    } else {
                        $starableType = ModuleableType::STAR;
                        //获取当前的艺人
                        $star_list = $trail->expectations()->get()->toArray();
                        if(count($star_list)!=0){
                            $stars = array_column($star_list,'name');
                            $start = implode(",",$stars);
                        }

                    }
                    //删除之前的目标艺人或者博主
                    TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::EXPECTATION)->delete();
                    foreach ($payload['expectations'] as $expectation) {
                        $starId = hashid_decode($expectation);
                        if ($starableType == ModuleableType::BLOGGER) {
                            if ($blogger = Blogger::find($starId))
                                $end .= ",".$blogger->nickname;
                                TrailStar::create([
                                    'trail_id' => $trail->id,
                                    'starable_id' => $starId,
                                    'starable_type' => $starableType,
                                    'type' => TrailStar::EXPECTATION,
                                ]);
                        } else {
                            if ($star = Star::find($starId))
                                $end .= ",".$star->name;
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::EXPECTATION,
                            ]);
                        }
                    }
                    if($starableType == ModuleableType::BLOGGER){
                        $title = "关联目标博主";
                    }else{
                        $title = "关联目标艺人";
                    }

                    $operateName = new OperateEntity([
                        'obj' => $trail,
                        'title' => $title,
                        'start' => $start,
                        'end' => trim($end,","),
                        'method' => OperateLogMethod::UPDATE,
                    ]);
                    $arrayOperateLog[] = $operateName;
                }catch (\Exception $e){
                    return $this->response->errorInternal("目标艺人关联失败");
                }


            }

            if ($request->has('recommendations') && is_array($payload['recommendations'])) {
                try{
                    $start = null;
                    $end = null;
                    if ($trail->type == Trail::TYPE_PAPI) {
                        $starableType = ModuleableType::BLOGGER;
                        //当前关联的博主
                        $blogger_list = $trail->bloggerRecommendations()->get()->toArray();
                        $bloggers = array_column($blogger_list,'nickname');
                        $start = implode(",",$bloggers);
                    } else {
                        $starableType = ModuleableType::STAR;
                        $star_list = $trail->recommendations()->get()->toArray();
                        $stars = array_column($star_list,'name');
                        $start = implode(",",$stars);
                    }
                    TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::RECOMMENDATION)->delete();
                    foreach ($payload['recommendations'] as $recommendation) {
                        $starId = hashid_decode($recommendation);

                        if ($starableType == ModuleableType::BLOGGER) {
                            if ($blogger = Blogger::find($starId))
                                $end .= $blogger->nickname;
                                TrailStar::create([
                                    'trail_id' => $trail->id,
                                    'starable_id' => $starId,
                                    'starable_type' => $starableType,
                                    'type' => TrailStar::RECOMMENDATION,
                                ]);
                        } else {
                            if ($star = Star::find($starId))
                                $end .= $star->name;
                                TrailStar::create([
                                    'trail_id' => $trail->id,
                                    'starable_id' => $starId,
                                    'starable_type' => $starableType,
                                    'type' => TrailStar::RECOMMENDATION,
                                ]);
                        }
                    }
                    if($start != $end){
                        if($starableType == ModuleableType::BLOGGER){
                            $title = "关联推荐博主";
                        }else{
                            $title = "关联推荐艺人";
                        }
                        $operateName = new OperateEntity([
                            'obj' => $trail,
                            'title' => $title,
                            'start' => $start,
                            'end' => trim($end,","),
                            'method' => OperateLogMethod::UPDATE,
                        ]);
                        $arrayOperateLog[] = $operateName;
                    }

                }catch (\Exception $e){
                    return $this->response->errorInternal("推荐艺人关联失败");
                }
            }

            event(new OperateLogEvent($arrayOperateLog));
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

                $user = Auth::guard('api')->user();
                $title = $trail->title." 锁价金额为".$payload['fee'].'元';  //通知消息的标题
                $subheading = $trail->title." 锁价金额为".$payload['fee'].'元';
                $module = Message::PROJECT;
                $link = URL::action("TrailController@detail", ["trail" => $trail->id]);
                $data = [];
                $data[] = [
                    "title" => '线索名臣', //通知消息中的消息内容标题
                    'value' => $trail->title,  //通知消息内容对应的值
                ];
                $data[] = [
                    'title' => '预计订单费用',
                    'value' => $payload['fee'],
                ];
                //TODO 发给papi商务组，商务组暂时没建立
//            $participant_ids = isset($payload['participant_ids']) ? $payload['participant_ids'] : null;
//            $authorization = $request->header()['authorization'][0];
//
//            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $participant_ids);
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

    public function detail(Request $request, Trail $trail)
    {
        $trail = $trail->searchData()->find($trail->id);
        if ($trail == null) {
            return $this->response->errorInternal("你没有查看该数据的权限");
        }
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
                    ->searchData()
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
            ->searchData()
            ->get();

        return $this->response->collection($trails, new TrailTransformer());
    }

    public function refuse(RefuseTrailReuqest $request, Trail $trail)
    {
        $power = (new ScopeRepository())->checkMangePower($trail->creator_id, $trail->principal_id, []);
        if (!$power) {
            return $this->response->errorInternal("你没有更改线索状态的权限");
        }
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

        return $this->response->accepted(null, '线索已拒绝');
    }

    public function filter(FilterTrailRequest $request)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $trails = Trail::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('status') && !is_null($payload['status']))
                $query->where('progress_status', $payload['status']);
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }
        })->searchData()->orderBy('created_at', 'desc')->paginate($pageSize);

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
    public function getFilter(TrailFilterRequest $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));

        $user = Auth::guard('api')->user();
        $company = $user->company->name;

        $joinSql = FilterJoin::where('company', $company)->where('table_name', 'trails')->first()->join_sql;

        $query = DB::table('trails')->selectRaw('DISTINCT(trails.id) as ids')->from(DB::raw($joinSql));

        $keyword = $request->get('keyword', '');
        if ($keyword !== '') {
            // todo 本表中字符型字段模糊查询; 本表中枚举使用的字段也需要加入
            $query->whereRaw('CONCAT(`trails`.`title`,`trails`.`brand`,`trails`.`desc`) LIKE "%?%"', [$keyword]);
        }

        $conditions = $request->get('conditions');
        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $type = $condition['type'];
            if ($operator == 'LIKE') {
                $value = '%' . $condition['value'] . '%';
                $query->whereRaw("$field $operator ?", [$value]);
            } else if ($operator == 'in') {
                $value = $condition['value'];
                if ($type >= 5)
                    foreach ($value as &$v) {
                        $v = hashid_decode($v);
                    }
                unset($v);
                $query->whereIn($field, $value);
            } else {
                $value = $condition['value'];
                $query->whereRaw("$field $operator ?", [$value]);
            }

        }
        $sql_with_bindings = str_replace_array('?', $query->getBindings(), $query->toSql());
//        dd($sql_with_bindings);
        $result = $query->pluck('ids')->toArray();

        $trails = Trail::whereIn('id', $result)->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails, new TrailTransformer());
    }

    public function import()
    {

    }

    public function export(Request $request)
    {
        $file = '当前线索导出' . date('YmdHis', time()) . '.xlsx';
        return (new TrailsExport())->download($file);
    }
}
