<?php

namespace App\Http\Controllers;

use App\Http\Requests\Calendar\EditCalendarRequest;
use App\Http\Requests\Calendar\StoreCalendarRequest;
use App\Http\Requests\Calendar\StoreCalendarTaskRequest;
use App\Http\Transformers\CalendarTransformer;
use App\Models\Calendar;
use App\ModuleableType;
use App\ModuleUserType;
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

    public function all(Request $request)
    {
        // todo 按权限筛选
        $user = Auth::guard("api")->user();
        $calendars  = Calendar::select(DB::raw('distinct calendars.id'),'calendars.*')->leftJoin('module_users as mu',function ($join){
            $join->on('moduleable_id','calendars.id')
                ->where('moduleable_type',ModuleableType::CALENDAR);
        })->where(function ($query)use ($user){
            $query->where('calendars.creator_id',$user->id);//创建人
            $query->orWhere([['mu.user_id',$user->id],['calendars.privacy',Calendar::SECRET]]);//参与人
            $query->orwhere('calendars.privacy',Calendar::OPEN);
        })->get();

        return $this->response->collection($calendars, new CalendarTransformer());
    }

    public function store(StoreCalendarRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        if ($request->has('star')) {
            $payload['starable_id'] = hashid_decode($payload['star']);


            //todo 暂时为硬编码
            if ($user->company->name != '泰洋川禾') {
                $payload['starable_type'] = ModuleableType::BLOGGER;//博主
            } else {
                $payload['starable_type'] = ModuleableType::STAR;//艺人
            }
        }

        $payload['creator_id'] = $user->id;

        DB::beginTransaction();
        //todo 加参与人
        try {
            $calendar = Calendar::create($payload);
            if (!$request->has('participant_ids') || !is_array($payload['participant_ids']))
                $payload['participant_ids'] = [];

            $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $calendar, ModuleUserType::PARTICIPANT);

        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->item($calendar, new CalendarTransformer());
    }
    public function storeCalendarTask(StoreCalendarTaskRequest $request, Calendar $calendar)
    {
        $payload = $request->all();
        if ($calendar && $calendar->id) {
            $array['resourceable_id'] = $calendar->id;
            $array['resourceable_title'] = $calendar->nickname;
            $array['resourceable_type'] = 'blogger';

        }

        DB::beginTransaction();
        //todo 加参与人
        try {
            $calendar = Calendar::create($payload);
            if (!$request->has('participant_ids') || !is_array($payload['participant_ids']))
                $payload['participant_ids'] = [];

            $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $calendar, ModuleUserType::PARTICIPANT);

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
            $payload['starable_id'] = hashid_decode($payload['star']);
            //todo 暂时为硬编码
            if ($user->company->name != '泰洋川禾') {
                $payload['starable_type'] = ModuleableType::BLOGGER;
            } else {
                $payload['starable_type'] = ModuleableType::STAR;
            }
        }

        if (!$request->has('participant_ids') || !is_array($payload['participant_ids']))
            $payload['participant_ids'] = [];

        if (!$request->has('participant_del_ids') || !is_array($payload['participant_del_ids']))
            $payload['participant_del_ids'] = [];

        try {
            $calendar->update($payload);

            $this->moduleUserRepository->addModuleUser($payload['participant_ids'], $payload['participant_del_ids'], $calendar, ModuleUserType::PARTICIPANT);
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
        return $this->response->item($calendar, new CalendarTransformer());
    }

    public function delete(Request $request, Calendar $calendar)
    {
        $user = Auth::guard('api')->user();
        if($calendar->creator_id != $user->id){
            return $this->response->errorInternal("你没有该日历的权限");
        }
        $calendar->status = Calendar::STATUS_FROZEN;//关闭
        $calendar->save();
        $calendar->delete();
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