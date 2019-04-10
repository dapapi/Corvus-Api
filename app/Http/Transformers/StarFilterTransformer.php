<?php

namespace App\Http\Transformers;

use App\Models\Calendar;
use App\Models\Schedule;
use App\Models\Star;
use App\ModuleableType;
use App\PrivacyType;
use App\ModuleUserType;
use App\TaskStatus;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StarFilterTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    protected $availableIncludes = ['creator', 'tasks', 'trails','affixes','broker', 'publicity','project','works','calendar','schedule','contracts'];

    public function transform(Star $star)
    {
        $array = [
            'id' => hashid_encode($star->id),
            'flag'   =>  ModuleableType::STAR,
            'name' => $star->name,


        ];
        $arraySimple = [
            'id' => hashid_encode($star->id),
            'flag'   =>  ModuleableType::STAR,
            'name' => $star->name,
        ];

        return $this->isAll ? $array : $arraySimple;
    }

    public function includeCreator(Star $star)
    {
        $user = $star->creator()->first();
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeBroker(Star $star)
    {
        $users = $star->broker()->get();
        return $this->collection($users, new UserTransformer());
    }

    public function includeTasks(Star $star)
    {
        $tasks = $star->tasks()->stopAsc()
            ->where('status',TaskStatus::NORMAL)->searchData()
            ->limit(3)->get();
        return $this->collection($tasks, new TaskTransformer());
    }

    public function includeAffixes(Star $star)
    {
        $affixes = $star->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
    public function includeProject(Star $star)
    {
        $project = $star->project()->get();
        return $this->collection($project,new ProjectTransformer());
    }
    public function includeWorks(Star $star)
    {
        $works = $star->works()->get();
        return $this->collection($works,new WorkTransformer());
    }
    public function includeTrails(Star $star)
    {
        $trails = $star->trails()->get();
        if ($trails->count() == 0){
            return $this->null();
        }
        return $this->collection($trails,new TrailTransformer());
    }
    public function includePublicity(Star $star){
        $users = $star->publicity()->get();
        return $this->collection($users,new UserTransformer());
    }
    public function includeCalendar(Star $star)
    {
        $calendars = $star->calendar()->first();
        if($calendars){
            return $this->item($calendars,new CalendarTransformer());
        }else{
            return null;
        }
    }
    public function includeContracts(Star $star)
    {

        $contracts = $star->contracts()->first();
        if($contracts){
            return $this->item($contracts, new ContractDateTransformer(false));
        }else{
            return null;
        }
    }
    public function includeSchedule(Star $star)
    {
        $data =  $star->calendar()->select(['id'])->first();
        if(!$data){
            return null;
        }
        $calendar_ids = $star->calendar()
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
//            $sql_with_bindings = str_replace_array('?', $schedules->getBindings(), $schedules->toSql());
//        dd($sql_with_bindings);

            return $this->collection($schedules,new ScheduleTransformer());

        }else{
            return null;
        }
    }
}
