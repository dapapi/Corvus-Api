<?php

namespace App\Http\Controllers;

use App\AffixType;
use App\Http\Requests\Schedule\EditScheduleRequest;
use App\Http\Requests\Schedule\IndexScheduleRequest;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\ScheduleRequest;
use App\Http\Transformers\ScheduleTransformer;
use App\Models\Calendar;
use App\Models\Material;
use App\Models\Module;
use App\Models\ProjectResource;
use App\Models\Schedule;
use App\Models\TaskResource;
use App\ModuleableType;
use App\ModuleUserType;
use App\Repositories\AffixRepository;
use App\Repositories\ModuleUserRepository;
use App\Repositories\ScheduleRepository;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    protected $moduleUserRepository;
    protected $affixRepository;

    public function __construct(ModuleUserRepository $moduleUserRepository, AffixRepository $affixRepository)
    {
        $this->moduleUserRepository = $moduleUserRepository;
        $this->affixRepository = $affixRepository;
    }

    public function index(IndexScheduleRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard("api")->user();
        if ($request->has('material_ids')) {
            foreach ($payload['material_ids'] as &$id) {
                $id = hashid_decode($id);
            }
            unset($id);
        }
        //查当前登录用户在日程参与人但不在日历参与人的日程列表
        $sql = "SELECT cc.id,cc.participant_ids,ss.id as schedule_id,ss.participant_ids as schedule_participant_ids  from
	(
	SELECT c.id,GROUP_CONCAT(mu.user_id) as participant_ids,c.deleted_at from calendars as c LEFT JOIN module_users as mu on mu.moduleable_id = c.id and mu.moduleable_type = 'calendar'  GROUP BY c.id
	) as cc
	LEFT JOIN (
		SELECT s.id,s.calendar_id,GROUP_CONCAT(mu2.user_id) as participant_ids,s.deleted_at from schedules as s left join module_users as mu2 on mu2.moduleable_id = s.id and mu2.moduleable_type = 'schedule'  GROUP BY s.id
		) as ss on ss.calendar_id = cc.id where (not FIND_IN_SET({$user->id},cc.participant_ids) or cc.participant_ids is null) and FIND_IN_SET({$user->id},ss.participant_ids) and cc.deleted_at is null and ss.deleted_at is null";
        $schedules_list1 = array_column(DB::select($sql),'schedule_id');

        if ($request->has('calendar_ids')) {
            foreach ($payload['calendar_ids'] as &$id) {
                $id = hashid_decode($id);
            }
            unset($id);
        }

        $payload['start_date'] = $payload['start_date'].' 00:00:00';
        $payload['end_date'] = $payload['end_date'] . ' 23:59:59';

        $schedules = Schedule::where(function ($query) use ($payload) {
            $query->where('start_at', '>', $payload['start_date'])->where('end_at', '<', $payload['end_date']);
        });

        //->orWhere(function ($query) use ($payload) {
        //            $query->where('start_at', '<', $payload['start_date'])->where('end_at', '>', $payload['end_date']);
        //        })->orWhere(function ($query) use ($payload) {
        //            $query->where('end_at', '>', $payload['start_date'])->where('end_at', '<', $payload['end_date']);
        //        })
        $schedules->where(function ($query) use ($request, $payload) {
            if ($request->has('material_ids'))
                $query->whereIn('material_id', $payload['material_ids']);

            if ($request->has('calendar_ids'))
                $query->whereIn('calendar_id', $payload['calendar_ids']);
        });

        //对查询进行限制
        //日程仅参与人可见
        $subquery = DB::table("schedules as s")->leftJoin('module_users as mu',function ($join){
            $join->on('mu.moduleable_id','s.id')
                ->where(DB::raw("mu.moduleable_type='".ModuleableType::SCHEDULE."'"));
        })->select('mu.user_id')->where(DB::raw("s.id=schedules.id"));

         $schedules->where(function ($query)use ($user,$subquery){
             $query->where('privacy',Schedule::OPEN);
             $query->orWhere('creator_id',$user->id);
             $query->orWhere('privacy',Schedule::SECRET)->where(DB::raw("$user->id in ({$subquery->toSql()})"));
         })->mergeBindings($subquery);


        $schedules_list2 = array_column($schedules->get()->toarray(),'id');

        $schedules_list = array_unique(array_merge($schedules_list2,$schedules_list2));
        $schedules = Schedule::whereIn('id',$schedules_list)->get();
        return $this->response->collection($schedules, new ScheduleTransformer());
    }

    public function store(StoreScheduleRequest $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        if ($request->has('calendar_id'))
            $payload['calendar_id'] = hashid_decode($payload['calendar_id']);
        $calendar = Calendar::find($payload['calendar_id']);
        if(!$calendar)
            $this->response->errorInternal("日历不存在");
        $participants = array_column($calendar->participants()->get()->toArray(),'id');
        if($user->id != $calendar->id && !in_array($user->id,$participants))
            $this->response->errorInternal("你没有权限添加日程");
        if ($request->has('material_id'))
            $payload['material_id'] = hashid_decode($payload['material_id']);

        $module = Module::where('code', 'schedules')->first();
        DB::beginTransaction();
        try {
            $schedule = Schedule::create($payload);
            if ($request->has('participant_ids') && is_array($payload['participant_ids']))

                $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $schedule, ModuleUserType::PARTICIPANT);

            if ($request->has('project_ids')) {
                foreach ($payload['project_ids'] as &$id) {
                    $id = hashid_decode($id);
                    ProjectResource::create([
                        'project_id' => $id,
                        'resourceable_id' => $schedule->id,
                        'resourceable_type' => ModuleableType::SCHEDULE,
                        'resource_id' => $module->id,
                    ]);
                }
                unset($id);
            }

//            if ($request->has('task_ids')) {
//                foreach ($payload['task_ids'] as $id) {
//                    $id = hashid_decode($id);
//                    TaskResource::create([
//                        'task_id' => $id,
//                        'resourceable_id' => $schedule->id,
//                        'resourceable_type' => ModuleableType::SCHEDULE,
//                        'resource_id' => $module->id,
//                    ]);
//                }
//            }

            if ($request->has('affix')) {
                foreach ($payload['affix'] as $affix) {
                    $this->affixRepository->addAffix($user, $schedule, $affix['title'], $affix['url'], $affix['size'], AffixType::DEFAULT);
                }
            }
        } catch (\Exception $exception) {
            dd($exception);
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('创建日程失败');
        }

        DB::commit();

        return $this->response->item($schedule, new ScheduleTransformer());
    }

    public function edit(EditScheduleRequest $request, Schedule $schedule)
    {

        $payload = $request->all();
        $user = Auth::guard("api")->user();
        if ($request->has('calendar_id')) {
            $payload['calendar_id'] = hashid_decode($payload['calendar_id']);
            $calendar = Calendar::find($payload['calendar_id']);
            if (!$calendar)
                return $this->response->errorBadRequest('日历id不存在');
            $participants = array_column($calendar->participants()->get()->toArray(),'id');
            if($user->id != $calendar->id && !in_array($user->id,$participants))
                $this->response->errorInternal("你没有权限添加日程");
        }

        if ($request->has('material_id') && $payload['material_id']) {
            $payload['material_id'] = hashid_decode($payload['material_id']);
            $material = Material::find($payload['material_id']);
            if (!$material)
                return $this->response->errorBadRequest('会议室id不存在');
        }


        if (!$request->has('participant_ids') || !is_array($payload['participant_ids']))
            $payload['participant_ids'] = [];

        if (!$request->has('participant_del_ids') || !is_array($payload['participant_del_ids']))
            $payload['participant_del_ids'] = [];

        DB::beginTransaction();
        try {
            $schedule->update($payload);
            $this->moduleUserRepository->addModuleUser($payload['participant_ids'], $payload['participant_del_ids'], $schedule, ModuleUserType::PARTICIPANT);

        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('更新日程失败');
        }
        DB::commit();

        return $this->response->accepted();
    }

    public function detail(Request $request, Schedule $schedule)
    {
        return $this->response->item($schedule, new ScheduleTransformer());
    }

    public function delete(Request $request, Schedule $schedule)
    {
        $calendar = $calendar = Calendar::find($schedule->calendar_id);
        $user = Auth::guard("api")->user();
        $participants = array_column($calendar->participants()->get()->toArray(),'id');
        if($user->id != $calendar->id && !in_array($user->id,$participants))
            $this->response->errorInternal("你没有权限删除日程");
        $schedule->delete();
        return $this->response->noContent();
    }

    public function recover(Request $request, Schedule $schedule)
    {
        $calendar = $calendar = Calendar::find($schedule->calendar_id);
        $user = Auth::guard("api")->user();
        $participants = array_column($calendar->participants()->get()->toArray(),'id');
        if($user->id != $calendar->id && !in_array($user->id,$participants))
            $this->response->errorInternal("你没有权限恢复日程");
        $schedule->restore();
        return $this->response->item($schedule, new ScheduleTransformer());
    }

    public function getCalendar(ScheduleRequest $request)
    {
        $starable_type = $request->get('starable_type', null);
        $starable_id = $request->get('starable_id', null);
        $starable_id = hashid_decode($starable_id);
        $date = $request->get('date', null);
        $calendar = ScheduleRepository::selectCalendar($starable_type, $starable_id, $date);
        return $calendar;
    }
}
