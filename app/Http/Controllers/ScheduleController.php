<?php

namespace App\Http\Controllers;

use App\AffixType;
use App\Events\CalendarMessageEvent;
use App\Events\OperateLogEvent;
use App\Helper\Message;
use App\Http\Requests\Schedule\EditScheduleRequest;
use App\Http\Requests\Schedule\IndexScheduleRequest;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\StoreScheduleTaskRequest;
use App\Http\Requests\ScheduleRequest;
use App\Http\Transformers\ScheduleTransformer;
use App\Http\Transformers\ScheduleRelateTransformer;
use App\Models\Blogger;
use App\Models\Calendar;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\ScheduleRelate;
use App\Models\Material;
use App\Models\Module;
use App\Models\Star;
use App\Models\Task;
use App\Models\ProjectResource;
use App\Models\Schedule;
use App\Models\TaskResource;
use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\Repositories\AffixRepository;
use App\Repositories\ScheduleRelatesRepository;
use App\Repositories\ModuleUserRepository;
use App\Repositories\ScheduleRepository;
use App\TriggerPoint\CalendarTriggerPoint;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    protected $moduleUserRepository;
    protected $affixRepository;

    public function __construct(ModuleUserRepository $moduleUserRepository, AffixRepository $affixRepository, ScheduleRelatesRepository $scheduleRelatesRepository)
    {
        $this->moduleUserRepository = $moduleUserRepository;
        $this->affixRepository = $affixRepository;
        $this->scheduleRelatesRepository = $scheduleRelatesRepository;

    }

    public function index(IndexScheduleRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard("api")->user();


        /*--------------开始----------------------*/
        if ($request->has('calendar_ids')) {
            foreach ($payload['calendar_ids'] as &$id) {
                $id = hashid_decode($id);
            }
            unset($id);
            //日程仅参与人可见
            $subquery = DB::table("schedules as s")->leftJoin('module_users as mu', function ($join) {
                $join->on('mu.moduleable_id', 's.id')
                    ->whereRaw("mu.moduleable_type='" . ModuleableType::SCHEDULE . "'")
                    ->whereRaw("mu.type='" . ModuleUserType::PARTICIPANT . "'");

            })->whereRaw("s.id=schedules.id")
                ->select('mu.user_id');
//->whereRaw("s.id=schedules.id")
            $schedules = Schedule::select('schedules.*')
                ->where(function ($query) use ($payload, $user, $subquery) {
                $query->where(function ($query) use ($payload) {
                    $query->where('privacy', Schedule::OPEN);
                    $query->whereIn('calendar_id', $payload['calendar_ids']);
                })->orWhere(function ($query) use ($user, $subquery) {
                    $query->orWhere('creator_id', $user->id);
                    $query->orWhere(function ($query) use ($user, $subquery) {
                        $query->where('privacy', Schedule::SECRET);
                        $query->whereRaw("$user->id in ({$subquery->toSql()})");
                    });
                });
            })->mergeBindings($subquery)
                ->where('start_at', '>', $payload['start_date'])->where('end_at', '<', $payload['end_date'])
                ->select('schedules.id','schedules.title','schedules.is_allday','schedules.privacy','schedules.start_at','schedules.end_at','schedules.position','schedules.repeat','schedules.desc')->get();
            return $this->response->collection($schedules, new ScheduleTransformer());
        }
        if ($request->has('material_ids')) {
            foreach ($payload['material_ids'] as &$id) {
                $id = hashid_decode($id);
            }
            unset($id);
            if ($payload['start_date'] == $payload['end_date']) {
                $payload['end_date'] = date('Y-m-d 23:59:59', strtotime($payload['end_date']));
            }

            $schedules = Schedule::select('schedules.*')->where('start_at', '<=', $payload['end_date'])->where('end_at', '>=', $payload['start_date'])
                ->leftJoin('calendars as c', 'c.id', 'schedules.calendar_id')//为了不查询出被删除的日历增加的连接查询
                ->leftJoin('users','users.id','schedules.creator_id')
                ->whereRaw('c.deleted_at is null')
                ->whereIn('material_id', $payload['material_ids'])
                ->select('schedules.id','schedules.title','schedules.is_allday','schedules.privacy','schedules.start_at','schedules.end_at','schedules.position','schedules.repeat','schedules.desc','users.icon_url')->get();
            return $this->response->collection($schedules, new ScheduleTransformer());
        }


    }

    public function listIndex(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard("api")->user();
        if (!$request->has('calendar_ids')) {
            $payload['calendar_ids'] = array();
        } else {
            foreach ($payload['calendar_ids'] as $key => &$id) {
                $id = hashid_decode($id);
            }
            unset($id);
        }

        $calendars = Calendar::select(DB::raw('distinct calendars.id'), 'calendars.id')->leftJoin('module_users as mu', function ($join) {
            $join->on('moduleable_id', 'calendars.id')
                ->where('moduleable_type', ModuleableType::CALENDAR);
        })->where(function ($query) use ($user) {
            $query->where('calendars.creator_id', $user->id);//创建人
            $query->orWhere([['mu.user_id', $user->id], ['calendars.privacy', Calendar::SECRET]]);//参与人
            $query->orwhere('calendars.privacy', Calendar::OPEN);
        })->get();
        $data = $calendars->toArray();
        if($request->has('calendar_ids')){
            $len = count($payload['calendar_ids']);
            for($i=0;$i<$len;$i++)
            {
                foreach ($data as  $key => $value){

                    if($value['id'] == $payload['calendar_ids'][$i])
                    {
                        unset($data[$key]);
                    }

                }
            }
          //  dd(array_diff($payload['calendar_ids'],$calendars->toArray()));
        }
        //日程仅参与人可见
        $subquery = DB::table("schedules as s")->leftJoin('module_users as mu', function ($join) {
            $join->on('mu.moduleable_id', 's.id')
                ->whereRaw("mu.moduleable_type='" . ModuleableType::SCHEDULE . "'")
                ->whereRaw("mu.type='" . ModuleUserType::PARTICIPANT . "'");
        })->whereRaw("s.id=schedules.id")->select('mu.user_id');

        $schedules = Schedule::select('schedules.*')
            ->where(function ($query) use ($payload, $user, $subquery, $calendars,$data) {

            $query->where(function ($query) use ($payload) {
                if ($payload['calendar_ids']) {
                    $query->where('privacy', Schedule::OPEN);
                    $query->whereIn('calendar_id', $payload['calendar_ids']);
                }
            })->orWhere(function ($query) use ($user, $subquery) {
                $query->orWhere('creator_id', $user->id);
                $query->orWhere(function ($query) use ($user, $subquery) {
                    $query->whereRaw("$user->id in ({$subquery->toSql()})");
                });
            })->whereNotIn('calendar_id', $data);

        })->mergeBindings($subquery)
            ->where('start_at', '>', $payload['start_date'])->where('end_at', '<', $payload['end_date'])
            ->select('schedules.id','schedules.title','schedules.calendar_id','schedules.creator_id','schedules.is_allday','schedules.privacy','schedules.start_at','schedules.end_at','schedules.position','schedules.repeat','schedules.desc')->get();
        return $this->response->collection($schedules, new ScheduleTransformer());
    }

    public function all(Request $request)
    {
        $payload = $request->all();
        if ($request->has('calendar_ids')) {
           $calendars_id = [];
            foreach ($payload['calendar_ids'] as $calendar_id) {
                $calendars_id[] = hashid_decode($calendar_id);
            }
            $schedules = Schedule::select('schedules.*')->where(function ($query) use ($payload,$calendars_id) {
                $query->where(function ($query) use ($payload,$calendars_id) {
                    $query->whereIn('calendar_id', $calendars_id);
                });
            })
                ->where('start_at', '>', $payload['start_date'])->where('end_at', '<', $payload['end_date'])
                ->select('schedules.id','schedules.title','schedules.is_allday','schedules.privacy','schedules.start_at',
                  'schedules.end_at','schedules.position','schedules.repeat','schedules.desc','schedules.calendar_id',
                  'schedules.creator_id')
                    ->get();
//                $sql_with_bindings = str_replace_array('?', $schedules->getBindings(), $schedules->toSql());
//               dd($sql_with_bindings);
                    return $this->response->collection($schedules, new ScheduleTransformer());
            }

    }


    public function hasauxiliary($request, $payload, $schedule, $module, $user)
    {

        if ($request->has('task_ids') && is_array($payload['task_ids'])) {
            $result = $this->scheduleRelatesRepository->addScheduleRelate($payload['task_ids'], $schedule, ModuleableType::TASK);
        }
        if ($request->has('project_ids') && is_array($payload['project_ids'])) {
            $result = $this->scheduleRelatesRepository->addScheduleRelate($payload['project_ids'], $schedule, ModuleableType::PROJECT);
        }

        if ($request->has('participant_ids') && is_array($payload['participant_ids']))

            $this->moduleUserRepository->addModuleUser($payload['participant_ids'], [], $schedule, ModuleUserType::PARTICIPANT);

//        if ($request->has('project_ids')) {
//            foreach ($payload['project_ids'] as &$id) {
//                $id = hashid_decode($id);
//                ProjectResource::create([
//                    'project_id' => $id,
//                    'resourceable_id' => $schedule->id,
//                    'resourceable_type' => ModuleableType::SCHEDULE,
//                    'resource_id' => $module->id,
//                ]);
//            }
//            unset($id);
//        }

        if ($request->has('affix')) {
            foreach ($payload['affix'] as $affix) {
                $this->affixRepository->addAffix($user, $schedule, $affix['title'], $affix['url'], $affix['size'], AffixType::DEFAULT);
            }
        }

    }

    public function hasrepeat($request, $payload, $module, $user)
    {

        $array = array();
        $array['end'] = date("Y", time()) . "-12-31 23:59:59";
        if ($payload['is_allday'] == 1) {
            // 开始时间   Ymd 格式
            $array['stime'] = date('Y-m-d', strtotime($payload['start_at']));
            $array['etime'] = date('Y-m-d', strtotime($payload['end_at']));
            $array['$ntime'] = date('Y-m-d', strtotime(now()));

        } else {
            $array['sstime'] = date('Y-m-d H:i:s', strtotime($payload['start_at']));
            $array['eetime'] = date('Y-m-d H:i:s', strtotime($payload['end_at']));
            $array['ntime'] = date('Y-m-d H:i:s', strtotime(now()));
        }
        if ($payload['is_allday'] == 1) {
            if ($payload['repeat'] == 0) {
                $schedule = Schedule::create($payload);
                $this->hasauxiliary($request, $payload, $schedule, $module, $user);
            } else if ($payload['repeat'] == 1) {
                $timestamp = strtotime($array['end']) - strtotime($array['etime']);
                $onedaytimestamp = 60 * 60 * 24;
                $sumtimestamp = $timestamp / $onedaytimestamp;
                for ($i = 0; $i < $sumtimestamp; $i++) {
                    $start_time = date('Y-m-d', strtotime($array['stime']) + $onedaytimestamp * $i);
                    $end_time = date('Y-m-d', strtotime($array['etime']) + $onedaytimestamp * $i);
                    $payload['start_at'] = $start_time;
                    $payload['end_at'] = $end_time;
                    $schedule = Schedule::create($payload);
                    $this->hasauxiliary($request, $payload, $schedule, $module, $user);
                }
            } else if ($payload['repeat'] == 2) {
                $timestamp = strtotime($array['end']) - strtotime($array['etime']);
                $onedaytimestamp = 60 * 60 * 24 * 7;
                $sumtimestamp = $timestamp / $onedaytimestamp;
                for ($i = 0; $i < $sumtimestamp; $i++) {
                    $start_time = date('Y-m-d', strtotime($array['stime']) + $onedaytimestamp * $i);
                    $end_time = date('Y-m-d', strtotime($array['etime']) + $onedaytimestamp * $i);
                    $payload['start_at'] = $start_time;
                    $payload['end_at'] = $end_time;
                    $schedule = Schedule::create($payload);
                    $this->hasauxiliary($request, $payload, $schedule, $module, $user);
                }

            } else if ($payload['repeat'] == 3) {
                $timestamp = strtotime($array['end']) - strtotime($array['etime']);
                $onedaytimestamp = 60 * 60 * 24 * 31;
                $sumtimestamp = ceil($timestamp / $onedaytimestamp);
                for ($i = 0; $i < $sumtimestamp; $i++) {
                    $start_time = date('Y-m-d', strtotime($array['stime']) + $onedaytimestamp * $i);
                    $end_time = date('Y-m-d', strtotime($array['etime']) + $onedaytimestamp * $i);
                    $payload['start_at'] = $start_time;
                    $payload['end_at'] = $end_time;
                    $schedule = Schedule::create($payload);
                    $this->hasauxiliary($request, $payload, $schedule, $module, $user);

                }
            }
        } else {
            if ($payload['repeat'] == 0) {
                $schedule = Schedule::create($payload);
                $this->hasauxiliary($request, $payload, $schedule, $module, $user);
            } else if ($payload['repeat'] == 1) {
                $timestamp = strtotime($array['end']) - strtotime($array['eetime']);
                $onedaytimestamp = 60 * 60 * 24;
                $sumtimestamp = ceil($timestamp / $onedaytimestamp);
                for ($i = 0; $i < $sumtimestamp; $i++) {
                    $start_time = date('Y-m-d H:i:s', strtotime($array['sstime']) + $onedaytimestamp * $i);
                    $end_time = date('Y-m-d H:i:s', strtotime($array['eetime']) + $onedaytimestamp * $i);
                    $payload['start_at'] = $start_time;
                    $payload['end_at'] = $end_time;
                    $schedule = Schedule::create($payload);
                    $this->hasauxiliary($request, $payload, $schedule, $module, $user);


                }
            } else if ($payload['repeat'] == 2) {
                $timestamp = strtotime($array['end']) - strtotime($array['eetime']);
                $onedaytimestamp = 60 * 60 * 24 * 7;
                $sumtimestamp = ceil($timestamp / $onedaytimestamp);
                for ($i = 0; $i < $sumtimestamp; $i++) {
                    $start_time = date('Y-m-d H:i:s', strtotime($array['sstime']) + $onedaytimestamp * $i);
                    $end_time = date('Y-m-d H:i:s', strtotime($array['eetime']) + $onedaytimestamp * $i);
                    $payload['start_at'] = $start_time;
                    $payload['end_at'] = $end_time;
                    $schedule = Schedule::create($payload);
                    $this->hasauxiliary($request, $payload, $schedule, $module, $user);
                }
            } else if ($payload['repeat'] == 3) {
                $timestamp = strtotime($array['end']) - strtotime($array['eetime']);
                $onedaytimestamp = 60 * 60 * 24 * 31;
                $sumtimestamp = ceil($timestamp / $onedaytimestamp);
                for ($i = 0; $i < $sumtimestamp; $i++) {
                    $start_time = date('Y-m-d H:i:s', strtotime($array['sstime']) + $onedaytimestamp * $i);
                    $end_time = date('Y-m-d H:i:s', strtotime($array['eetime']) + $onedaytimestamp * $i);
                    $payload['start_at'] = $start_time;
                    $payload['end_at'] = $end_time;
                    $schedule = Schedule::create($payload);
                    $this->hasauxiliary($request, $payload, $schedule, $module, $user);
                }

            }
        }
        return $schedule;
    }

    public function store(StoreScheduleRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;
        if(!$request->has('repeat') || $payload['repeat'] == null)
            $payload['repeat'] = '0';
        if ($request->has('calendar_id'))
            $payload['calendar_id'] = hashid_decode($payload['calendar_id']);
        $calendar = Calendar::find($payload['calendar_id']);

        if (!$calendar)
            $this->response->errorInternal("日历不存在");
        $participants = array_column($calendar->participants()->get()->toArray(), 'id');
        if ($calendar->privacy == Calendar::SECRET && ($user->id != $calendar->creator_id && !in_array($user->id, $participants)))
            $this->response->errorInternal("你没有权限添加日程");
        if ($request->has('material_id'))
            $payload['material_id'] = hashid_decode($payload['material_id']);
        if ($request->has('material_id') && $payload['material_id']) {
            if ($payload['is_allday'] == 1) {
                // 开始时间   Ymd 格式
                $array['start_at'] = date('Y-m-d', strtotime($payload['start_at']));
                $array['end_at'] = date('Y-m-d', strtotime($payload['end_at']));

            } else {
                $array['start_at'] = date('Y-m-d H:i:s', strtotime($payload['start_at']));
                $array['end_at'] = date('Y-m-d H:i:s', strtotime($payload['end_at']));

            }
            $materials['material_id'] = ['material_id', $payload['material_id']];
            $materials['start_at'] = ['end_at', '>=', $array['start_at']];
            $materials['end_at'] = ['start_at', '<=', $array['end_at']];
            $endmaterials = Schedule::where($materials['material_id'][0], $materials['material_id'][1])
                ->where($materials['end_at'][0], $materials['end_at'][1], $materials['end_at'][2])
                ->where($materials['start_at'][0], $materials['start_at'][1], $materials['start_at'][2])
                ->orderby('start_at')->get(['id'])->toArray();
            if ($endmaterials) {
                $this->response->errorForbidden("该时段会议室已被占用");
            }
        }

        $module = Module::where('code', 'schedules')->first();

        DB::beginTransaction();
        try {
            $schedule = $this->hasrepeat($request, $payload, $module, $user);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $schedule,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));

            //向参与人发消息
            $authorization = $request->header()['authorization'][0];
            event( new CalendarMessageEvent($schedule,CalendarTriggerPoint::CREATE_SCHEDULE,$authorization,$user));

            //获取日历对象的艺人
            $star_calendar = Calendar::where('id',$schedule->calendar_id)->select('starable_id','type')->first();
//            if($star_calendar){
//                $module = null;
//                if ($star_calendar->type == ModuleableType::BLOGGER){
//                    $module = Blogger::findOrFail($star_calendar->starable_id);
//                }else{
//                    $module = Star::findOrFail($star_calendar->starable_id);
//                }
//                if ($module){
//                    $operate = new OperateEntity([
//                        'obj' => $module,
//                        'title' => $schedule->title,
//                        'start' => null,
//                        'end' => null,
//                        'method' => OperateLogMethod::CREATE_STAR_SCHEDULE,
//                    ]);
//                    event(new OperateLogEvent([
//                        $operate
//                    ]));
//                }
//            }

        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('创建日程失败');
        }
        DB::commit();
        //向参与人发送消息


        return $this->response->item($schedule, new ScheduleTransformer());
    }

    public function storeSchedulesTask(StoreScheduleTaskRequest $request, Schedule $schedule)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {

            if ($request->has('task_ids') && is_array($payload['task_ids'])) {
                $result = $this->scheduleRelatesRepository->addScheduleRelate($payload['task_ids'], $schedule, ModuleableType::TASK);
            }

            if ($request->has('project_ids') && is_array($payload['project_ids'])) {

                $result = $this->scheduleRelatesRepository->addScheduleRelate($payload['project_ids'], $schedule, ModuleableType::PROJECT);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
        DB::commit();
        return $this->response->accepted();

    }

    public function indexSchedulesTask(Request $request, Schedule $schedule)
    {
        $payload = $request->all();
        $array['schedule_id'] = $schedule->id;
        if ($request->has('type')) {//姓名
            $array[] = ['moduleable_type', $payload['type']];
        }
        $schedules = ScheduleRelate::where($array)->createDesc()->get();
        return $this->response->collection($schedules, new ScheduleRelateTransformer());

    }

    public function removeSchedulesTask(Request $request, Schedule $schedule)
    {
        $payload = $request->all();
        $array['schedule_id'] = $schedule->id;
        if ($request->has('delete_id')) {
            $array[] = ['id', hashid_decode($payload['delete_id'])];
            ScheduleRelate::where($array)->delete();
        }
    }

    public function removeoneSchedulesRelate(Schedule $schedule, $model)
    {

        $array['schedule_id'] = $schedule->id;
        if ($model instanceof Task && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::TASK;
        } else if ($model instanceof Project && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::PROJECT;
        }
        $is_ture = ScheduleRelate::where($array)->delete();
        if (!$is_ture) {
            $this->response->errorInternal("删除失败");
        }
    }

    public function edit(EditScheduleRequest $request, Schedule $schedule)
    {
        $old_schedule = clone $schedule;//复制日程，以便发消息
        $users = $this->getEditPowerUsers($schedule);
        $user = Auth::guard("api")->user();
        if (!in_array($user->id, $users)) {
            return $this->response->errorInternal("你没有编辑该日程的权限");
        }
        $payload = $request->all();
        if ($request->has('calendar_id')) {
            $payload['calendar_id'] = hashid_decode($payload['calendar_id']);
            $calendar = Calendar::find($payload['calendar_id']);
            if (!$calendar)
                return $this->response->errorBadRequest('日历id不存在');
//            $participants = array_column($calendar->participants()->get()->toArray(), 'id');
//            if ($user->id != $calendar->creator_id && !in_array($user->id, $participants))
//                $this->response->errorInternal("你没有权限修改日程");
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

            if ($old_schedule->title != $schedule->title){
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $schedule,
                    'title' => "日程标题",
                    'start' => $old_schedule->title,
                    'end' => $schedule->title,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate
                ]));
            }
            if ($old_schedule->start_at != $schedule->start_at || $old_schedule->end_at != $schedule->end_at){
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $schedule,
                    'title' => "日程时间",
                    'start' => $old_schedule->start_at."-".$old_schedule->end_at,
                    'end' => $schedule->start_at."-".$schedule->end_at,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate
                ]));
            }
            $start_participants = implode(",",array_column($old_schedule->participants()->get()->toArray(),'name'));
            $end_participants = implode(",",array_column($schedule->participants()->get()->toArray(),'name'));
            if ($start_participants != $end_participants){
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $schedule,
                    'title' => "参与人",
                    'start' => $start_participants,
                    'end' => $end_participants,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate
                ]));
            }

            if ($old_schedule->material_id != $schedule->material_id){
                $old_material = $old_schedule->material()->first();
                $material = $schedule->material()->first();

                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $schedule,
                    'title' => "会议室",
                    'start' => $old_material == null ? null : $old_material->name,
                    'end' => $material == null ? null : $material->name,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate
                ]));

            }

            if($old_schedule->position != $schedule->position){
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $schedule,
                    'title' => "位置",
                    'start' => $old_schedule->position,
                    'end' => $schedule->position,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate
                ]));
            }
            if($old_schedule->remind != $schedule->remind){
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $schedule,
                    'title' => "提醒",
                    'start' => $old_schedule->remind,
                    'end' => $schedule->remind,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate
                ]));
            }
            if($old_schedule->repeat != $schedule->repeat){
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $schedule,
                    'title' => "重复",
                    'start' => $old_schedule->repeat,
                    'end' => $schedule->repeat,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate
                ]));
            }


            $this->hasauxiliary($request, $payload, $schedule, '', $user);
            $this->moduleUserRepository->addModuleUser($payload['participant_ids'], $payload['participant_del_ids'], $schedule, ModuleUserType::PARTICIPANT);
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('更新日程失败');
        }
        DB::commit();
        //向参与人发消息
        $authorization = $request->header()['authorization'][0];
        $meta = ["old_schedule"=>$old_schedule];
        event( new CalendarMessageEvent($schedule,CalendarTriggerPoint::UPDATE_SCHEDULE,$authorization,$user,$meta));
        return $this->response->accepted();
    }

    public function detail(Request $request, Schedule $schedule)
    {
        $users = $this->getPowerUsers($schedule);
        $user = Auth::guard("api")->user();
        if (!in_array($user->id, $users)) {
            return $this->response->accepted();
        }
        // 操作日志
        $operate = new OperateEntity([
            'obj' => $schedule,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate
        ]));
        return $this->response->item($schedule, new ScheduleTransformer());
    }

    public function delete(Request $request, Schedule $schedule)
    {
        $users = $this->getPowerUsers($schedule);
        $user = Auth::guard("api")->user();
        if (!in_array($user->id, $users)) {
            $this->response->errorInternal("你没有权限删除日程");
        }

        $schedule->delete();
        return $this->response->noContent();
    }

    public function recover(Request $request, Schedule $schedule)
    {
        $calendar = $calendar = Calendar::find($schedule->calendar_id);
        $user = Auth::guard("api")->user();
        $participants = array_column($calendar->participants()->get()->toArray(), 'id');
        if ($user->id != $calendar->creator_id && !in_array($user->id, $participants))
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

    private function getPowerUsers($schedule)
    {
        $user = Auth::guard("api")->user();
        $users = [];//记录可以查看日程的用户id
        //日程的创建者，
        $users[] = $schedule->creator_id;
        //参与者
        $users = array_merge(array_column($schedule->participants()->get()->toArray(), 'id'), $users);
        //日程未勾选参与人可见,则日历的参与人和日历的创建人可删除
        if ($schedule->privacy == Schedule::OPEN) {
            $calendar = Calendar::find($schedule->calendar_id);
            if ($calendar != null) {
                if ($calendar->privacy == Calendar::OPEN){
                    $users[] = $user->id;
                }
                $users[] = $calendar->creator_id;
                $users = array_merge($users, array_column($calendar->participants()->get()->toArray(), 'id'));
            }
        }
        return $users;
    }
    //具有修改权限的用户
    private function getEditPowerUsers($schedule)
    {
        $users = [];//记录可以查看日程的用户id
        //日程的创建者，
        $users[] = $schedule->creator_id;
        //参与者
        $users = array_merge(array_column($schedule->participants()->get()->toArray(), 'id'), $users);
        //日程未勾选参与人可见,则日历的参与人和日历的创建人可删除
        if ($schedule->privacy == Schedule::OPEN) {
            $calendar = Calendar::find($schedule->calendar_id);
            if ($calendar != null) {
                $users[] = $calendar->creator_id;
                $users = array_merge($users, array_column($calendar->participants()->get()->toArray(), 'id'));
            }
        }
        return $users;
    }
}
