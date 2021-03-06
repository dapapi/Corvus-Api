<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\Calendar\EditCalendarRequest;
use App\Http\Requests\Calendar\StoreCalendarRequest;
use App\Http\Requests\Calendar\StoreCalendarTaskRequest;
use App\Http\Transformers\CalendarTransformer;
use App\Http\Transformers\CalendarIndexTransformer;
use App\Models\Calendar;
use App\Models\OperateEntity;
use App\Models\Schedule;
use App\ModuleableType;
use App\Models\ModuleUser;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\Repositories\CalendarRepository;
use App\Repositories\ModuleUserRepository;
use DemeterChain\C;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    protected $moduleUserRepository;

    public function __construct(ModuleUserRepository $moduleUserRepository)
    {
        $this->moduleUserRepository = $moduleUserRepository;
    }
    public function index(Request $request)
    {
        // todo 按权限筛选
        $payload = $request->all();
        $user = Auth::guard("api")->user();
        $array = [];//查询条件
        if($request->has('title')){//姓名
            $array[] = ['title','like','%'.$payload['title'].'%'];
        }

        $subquery = DB::table("calendars as s")->LeftJoin('module_users as mu', function ($join) {
            $join->on('mu.moduleable_id', 's.id')
                ->whereRaw("mu.moduleable_type='" . ModuleableType::CALENDAR . "'")
                ->whereRaw("mu.type='" . ModuleUserType::PARTICIPANT . "'");
        })->whereRaw("s.id=calendars.id")->select("mu.user_id");


        $calendars  = Calendar::select(DB::raw('distinct calendars.id'),'calendars.color','calendars.principal_id','calendars.privacy',
            'calendars.starable_type','calendars.title')
            ->where(function ($query)use ($user,$subquery){
            $query->where('calendars.creator_id',$user->id)//创建人
            ->orWhere(function ($query) use ($user, $subquery) {
                     $query->whereRaw("$user->id in ({$subquery->toSql()})")
                         ->where('calendars.privacy',Calendar::SECRET);//参与人
                 })
            ->orwhere('calendars.privacy',Calendar::OPEN);
        })->where('id','>',0)->where($array)
            ->get();
//        $sql_with_bindings = str_replace_array('?', $calendars->getBindings(), $calendars->toSql());
//                    dd($sql_with_bindings);

        return $this->response->collection($calendars, new CalendarIndexTransformer());
    }
    public function all(Request $request)
    {
        // todo 按权限筛选
        $payload = $request->all();
        $user = Auth::guard("api")->user();
        $array = [];//查询条件
        if($request->has('title')){//姓名
            $array[] = ['title','like','%'.$payload['title'].'%'];
        }
        $calendars  = Calendar::select(DB::raw('distinct calendars.id'),'calendars.*')->leftJoin('module_users as mu',function ($join){
            $join->on('moduleable_id','calendars.id')
                ->where('moduleable_type',ModuleableType::CALENDAR);
        })->where(function ($query)use ($user){
            $query->where('calendars.creator_id',$user->id);//创建人
            $query->orWhere([['mu.user_id',$user->id],['calendars.privacy',Calendar::SECRET]]);//参与人
            $query->orwhere('calendars.privacy',Calendar::OPEN);
        })->where($array)->get();

        return $this->response->collection($calendars, new CalendarTransformer());
    }

    public function store(StoreCalendarRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        if ($request->has('star')) {
            $payload['starable_id'] = hashid_decode($payload['star']['id']);

            if ($payload['star']['flag'] == 'blogger') {
                $payload['starable_type'] = ModuleableType::BLOGGER;//博主
            } else {
                $payload['starable_type'] = ModuleableType::STAR;//艺人
            }
            //判断艺人是否已经关联日历
            $calendars = Calendar::where('starable_type',$payload['starable_type'])->where('starable_id',$payload['starable_id'])->get()->toArray();
            if(count($calendars) >= 1){
                return $this->response->errorInternal("该艺人已存在关联日历");
            }
        }
        $payload['creator_id'] = $user->id;
        if ($request->has('principal_id'))
            $payload['principal_id'] = hashid_decode($payload['principal_id']);
        DB::beginTransaction();
        //todo 加参与人
        try {
            if($request->has('star') && $payload['star']['flag'] == 'star'){
                $calendar = Calendar::create($payload);
            }else{
                $calendar = Calendar::create($payload);
            }

            if (!$request->has('participant_ids') || !is_array($payload['participant_ids']))
                $payload['participant_ids'] = [];

            $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $calendar, ModuleUserType::PARTICIPANT);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $calendar,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->item($calendar, new CalendarTransformer());
    }
    public function edit(EditCalendarRequest $request, Calendar $calendar)
    {
        //todo 日历权限硬编码
        $user = Auth::guard('api')->user();
        //获取参与人列表
        $participants = array_column($calendar->participants()->get()->toArray(),'id');
        if($calendar->privacy == Calendar::SECRET && ($calendar->creator_id != $user->id && !in_array($user->id,$participants))){
            return $this->response->errorInternal("你没有编辑日历的权限");
        }
        $payload = $request->all();
        if ($request->has('star')) {
            $payload['starable_id'] = hashid_decode($payload['star']['id']);
            if ($payload['star']['flag'] == 'blogger') {
                $payload['starable_type'] = ModuleableType::BLOGGER;//博主
            } else {
                $payload['starable_type'] = ModuleableType::STAR;//艺人
            }
            //判断艺人是否已经关联日历
            $calendars = Calendar::where('starable_type',$payload['starable_type'])->where('id','!=',$calendar->id)->where('starable_id',$payload['starable_id'])->get()->toArray();
            if(count($calendars) >= 1){
                return $this->response->errorMethodNotAllowed("该艺人已存在相关日历");
            }
            // 艺人在该日历 下创建了日程也不能 修改    张峪铭
            $is_calendars = Schedule::where('calendar_id',$calendar->id)->get();
            if(count($is_calendars) >= 1){
                if($payload['starable_id'] != $calendar['starable_id'] || $payload['starable_type'] != $calendar['starable_type']){
                    return $this->response->errorMethodNotAllowed("该艺人已有相关日程，不能修改");
                }

            }
            //艺人在该日历 下创建了日程也不能 修改
        }
        if (!$request->has('participant_ids') || !is_array($payload['participant_ids']))
            $payload['participant_ids'] = [];

        if (!$request->has('participant_del_ids') || !is_array($payload['participant_del_ids']))
            $payload['participant_del_ids'] = [];
        try {
            //获取未更新之前的参与人
            $start_participants = implode(",",array_column($calendar->participants()->get(['name'])->toArray(),'name'));
            if ($request->has('principal_id'))
                $payload['principal_id'] = hashid_decode($payload['principal_id']);
            $this->moduleUserRepository->addModuleUserss($payload['participant_ids'], $payload['participant_del_ids'], $calendar, ModuleUserType::PARTICIPANT);
            if($request->has('principal_id') && $payload['principal_id'] != $calendar->principal_id)
            {

                $user_id = ModuleUser::where('moduleable_type',ModuleableType::CALENDAR)->where('moduleable_id',$calendar->id)->where('user_id',$payload['principal_id'])->get()->toArray();
                if(!$user_id){
                    ModuleUser::create(['moduleable_type'=>ModuleableType::CALENDAR,'moduleable_id'=>$calendar->id,'user_id'=>$payload['principal_id'],'type'=>1]);

                }
            }
            $calendar->update($payload);
            //更新之后的参与人
            $end_participants = implode(",",array_column($calendar->participants()->get(['name'])->toArray(),'name'));
            ///记录日志
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $calendar,
                'title' => "参与人",
                'start' => $start_participants,
                'end' => $end_participants,
                'method' => OperateLogMethod::UPDATE,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));

        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改日历失败');
        }

        return $this->response->accepted();
    }
    public function detail(Request $request, Calendar $calendar)
    {
        $user = Auth::guard('api')->user();
        //参与者

        $participants = array_column($calendar->participants()->get()->toArray(),'id');
        if($calendar->privacy == Calendar::SECRET && $user->id != $calendar->creator_id && !in_array($user->id,$participants)){
            return $this->response->errorInternal("你没有查看该日历的权限");
        }
        return $this->response->item($calendar, new CalendarIndexTransformer());
    }

    public function delete(Request $request, Calendar $calendar)
    {
        $user = Auth::guard('api')->user();

        $participants = array_column($calendar->participants()->get()->toArray(),'id');

        if($calendar->privacy == Calendar::SECRET && $user->id != $calendar->creator_id && !in_array($user->id,$participants)){
            return $this->response->errorInternal("你没有该日历的权限");
        }
        DB::beginTransaction();
        try {
            $calendar->status = Calendar::STATUS_FROZEN;//关闭
            $calendar->save();
            $calendar->delete();

           Schedule::where('calendar_id',$calendar->id)->delete();
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $calendar,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::DELETE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorBadRequest('上传文件排版有问题，请严格按照模版格式填写');
        }
        DB::commit();
        return $this->response->noContent();
    }

    public function recover(Request $request, Calendar $calendar)
    {
        $user = Auth::guard('api')->user();
        if($calendar->creator_id != $user->id){
            return $this->response->errorInternal("你没有恢复该日历的权限");
        }
        $calendar->restore();
        $calendar->status = Calendar::STATUS_NORMAL;
        $calendar->save();

        return $this->response->item($calendar, new CalendarTransformer());
    }

    public function forceDelete(Request $request, Calendar $calendar)
    {
        $user = Auth::guard('api')->user();
        if($calendar->creator_id != $user->id){
            return $this->response->errorInternal("你没有该日历的权限");
        }
        $calendar->forceDelete();
        return $this->response->noContent();
    }
}
