<?php

namespace App\Http\Transformers;
use App\PrivacyType;
use App\Models\Calendar;
use App\TaskStatus;
use App\Models\Schedule;
use App\ModuleableType;
use App\ModuleUserType;
use App\Models\Blogger;
use League\Fractal\TransformerAbstract;
use App\Models\PrivacyUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class BloggerFilterTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'producer', 'type','project', 'trails','publicity','operatelogs','relate_project_courses','calendar','schedule','contracts'];

 //   protected $defaultIncludes = ['type'];

    private $isAll;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Blogger $blogger)
    {

        $array = [
            'id' => hashid_encode($blogger->id),
            'flag'   =>  ModuleableType::BLOGGER,
            'nickname' => $blogger->nickname,

        ];

        $arraySimple = [
            'id' => hashid_encode($blogger->id),
            'flag'   =>  ModuleableType::BLOGGER,
            'nickname' => $blogger->nickname,

        ];
        return $this->isAll ? $array : $arraySimple;
    }

    public function includeCreator(Blogger $blogger)
    {
        $user = $blogger->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeProducer(Blogger $blogger)
    {
        $user = $blogger->producer;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeType(Blogger $blogger)
    {
        $type = $blogger->type;
        if (!$type)
            return null;
        return $this->item($type, new BloggerTypeTransformer());
    }
//    public function includeProducer(Blogger $blogger)
//    {
//        $producer = $blogger->producer;
//        if (!$producer)
//            return null;
//        return $this->item($producer, new BloggerProducerTransformer());
//    }
    public function includeTrails(Blogger $blogger)
    {
        $trails = $blogger->trail()->get();
        return $this->collection($trails,new TrailTransformer());
    }
    public function includeTasks(Blogger $blogger)
    {
        $tasks = $blogger->tasks()->searchData()
            ->where('status',TaskStatus::NORMAL)
            ->where('resourceable_id',$blogger->id)
            ->where('resourceable_type','blogger')
            ->stopAsc()->limit(3)->get();
        return $this->collection($tasks, new TaskTransformer());
    }
    public function includeOperateLogs(Blogger $blogger)
    {
        $tasks = $blogger->operateLogs()->createDesc()->limit(1)->get();
        return $this->collection($tasks, new OperateLogTransformer());
    }
    public function includeAffixes(Blogger $blogger)
    {
        $affixes = $blogger->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
    public function includePublicity(Blogger $blogger){
        $users = $blogger->publicity()->get();
        return $this->collection($users,new UserTransformer());
    }
    public function includeContracts(Blogger $blogger)
    {

        $contracts = $blogger->contracts()->first();
        if($contracts){
            return $this->item($contracts, new ContractDateTransformer(false));
        }else{
            return null;
        }
    }
    public function includeCalendar(Blogger $blogger)
    {
        $calendars = $blogger->calendars()->first();
        if($calendars){
            return $this->item($calendars,new CalendarTransformer());
        }else{
            return null;
        }
    }
    public function includeSchedule(Blogger $blogger)
    {

            $data =  $blogger->calendar()->select(['id'])->first();
            if(!$data){
                return null;
            }
            $calendar_ids = $blogger->calendar()
                ->select(['id'])->first()->toArray();//查找艺人日历
            if($calendar_ids){//日历存在查找日程
                $user = Auth::guard("api")->user();
                $calendars  = Calendar::select(DB::raw('distinct calendars.id'),'calendars.*')->leftJoin('module_users as mu',function ($join){
                    $join->on('moduleable_id','calendars.id')
                        ->where('moduleable_type',ModuleableType::CALENDAR);
                })->where(function ($query)use ($user){
                    $query->where('calendars.creator_id',$user->id);//创建人
                    $query->orWhere([['mu.user_id',$user->id],['calendars.privacy',Calendar::SECRET]]);//参与人
                    $query->orwhere('calendars.privacy',Calendar::OPEN);
                })->select('calendars.id')->get()->toArray();
                foreach ($calendars as  $key => $value){
                    $dataArr[] = $value['id'];
                }
                $arr = array_intersect($dataArr,$calendar_ids);
                if(!$arr){
                    return null;
                }
                //日程仅参与人可见
                $subquery = DB::table("schedules as s")->leftJoin('module_users as mu', function ($join) {
                    $join->on('mu.moduleable_id', 's.id')
                        ->whereRaw("mu.moduleable_type='" . ModuleableType::SCHEDULE . "'")
                        ->whereRaw("mu.type='" . ModuleUserType::PARTICIPANT . "'");
                })->whereRaw("s.id=schedules.id")->select('mu.user_id');
                $schedules = Schedule::select('schedules.*')->where(function ($query) use ($subquery,$user,$calendar_ids) {
                    $query->where(function ($query) use ($calendar_ids) {
                        $query->where('privacy', Schedule::OPEN);
                        $query->whereIn('calendar_id', $calendar_ids);
                    })->orWhere(function ($query) use ($user, $subquery) {
                        $query->Where('creator_id', $user->id);
                        $query->orwhere(function ($query) use ($user, $subquery) {
                            $query->whereRaw("$user->id in ({$subquery->toSql()})");
                        });
                    })->whereIn('calendar_id', $calendar_ids);
                })
                    ->select('schedules.id','schedules.title','schedules.is_allday','schedules.privacy','schedules.start_at',
                        'schedules.end_at','schedules.position','schedules.repeat','schedules.desc','schedules.calendar_id',
                        'schedules.creator_id',DB::raw("ABS(NOW() - start_at)  AS diffTime"))->orderBy('diffTime')
                    ->limit(3)->get();
//            $subquery = DB::table("schedules as s")->leftJoin('module_users as mu', function ($join) {
//                $join->on('mu.moduleable_id', 's.id')
//                    ->whereRaw("mu.moduleable_type='" . ModuleableType::SCHEDULE . "'")
//                    ->whereRaw("mu.type='" . ModuleUserType::PARTICIPANT . "'");
//
//            })->whereRaw("s.id=schedules.id")
//                ->select('mu.user_id');
//            $calendars = $calendars->schedules();
//              $calendar =  $calendars->where(function ($query) use ($user, $subquery){
//
//                  $query->where(function ($query) use ($user, $subquery) {
//                      $query->where('privacy', Schedule::OPEN)
//                          ->whereRaw("$user->id in ({$subquery->toSql()})");
//                  })->orWhere(function ($query) use ($user, $subquery) {
//                      $query->orWhere('creator_id', $user->id);
//                      $query->orWhere(function ($query) use ($user, $subquery) {
//                          $query->where('privacy', Schedule::SECRET);
//                          $query->whereRaw("$user->id in ({$subquery->toSql()})");
//                      });
//
//                  });
//                })->mergeBindings($subquery)
//                ->select('schedules.*',DB::raw("ABS(NOW() - start_at)  AS diffTime")) ->orderBy('diffTime')
//                ->limit(3)->get();
//            $sql_with_bindings = str_replace_array('?', $calendar->getBindings(), $calendar->toSql());
//        dd($sql_with_bindings);

            return $this->collection($schedules,new ScheduleTransformer());
        }else{
            return null;
        }
    }


}