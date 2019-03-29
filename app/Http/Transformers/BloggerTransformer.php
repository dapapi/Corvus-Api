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
class BloggerTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'producer', 'type','project', 'trails','publicity','operatelogs','relate_project_courses','calendar','schedule','contracts'];

    protected $defaultIncludes = ['type'];

    private $isAll;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Blogger $blogger)
    {
        //假头像
        $sub_str = substr($blogger->avatar,0,1);
        if ($sub_str == "#" || $sub_str == null){
            $blogger->avatar = "https://res-crm.papitube.com/image/artist-no-avatar.png";
        }

        $user = Auth::guard('api')->user();
        $setprivacy1 =array();
        $Viewprivacy2 =array();
        $array['moduleable_id']= $blogger->id;
        $array['moduleable_type']= ModuleableType::BLOGGER;
        $array['is_privacy']=  PrivacyType::OTHER;
        $setprivacy = PrivacyUser::where($array)->get(['moduleable_field'])->toArray();
        foreach ($setprivacy as $key =>$v){

            $setprivacy1[]=array_values($v)[0];

        }
        if(!$setprivacy1 && $blogger ->creator_id != $user->id){
            $array['user_id']= $user->id;
            $Viewprivacy = PrivacyUser::where($array)->get(['moduleable_field'])->toArray();
            unset($array);
            if($Viewprivacy){
                foreach ($Viewprivacy as $key =>$v){
                    $Viewprivacy1[]=array_values($v)[0];
                }
                $setprivacy1  = array_diff($setprivacy1,$Viewprivacy1);
            }else{
                $setprivacy1 = array();
            }

        }
        $array = [
            'id' => hashid_encode($blogger->id),
            'nickname' => $blogger->nickname,
            'moduleable_type'   =>  ModuleableType::BLOGGER,
            'platform_id' => $blogger->platform_id,    //平台
            'avatar' => $blogger->avatar,
            'communication_status' => $blogger->communication_status,//沟通状态
            'intention' => boolval($blogger->intention),//与我司签约意向
            'intention_desc' => $blogger->intention_desc,//不与我司签约原因
            'sign_contract_at' => $blogger->sign_contract_at,//签约日期
            'level' => $blogger->level,//博主级别
            'hatch_star_at' => $blogger->hatch_star_at,//孵化期开始时间
            'hatch_end_at' => $blogger->hatch_end_at,//孵化期结束时间
            'sign_contract_status' => $blogger->sign_contract_status,//签约状态
            'icon' => $blogger->icon, // 头像
            'desc' => $blogger->desc,//描述/备注
            'gender' => $blogger->gender,
            'cooperation_demand' => $blogger->cooperation_demand,//合作需求
            'terminate_agreement_at' => $blogger->terminate_agreement_at,//解约日期
            'sign_contract_other' => $blogger->sign_contract_other,//是否签约其他公司
            'sign_contract_other_name' => $blogger->sign_contract_other_name,//签约公司名称
            'status' => $blogger->status,
            'platform'=> $blogger->platform,
            'douyin_id' => $blogger->douyin_id,//微博url
            'douyin_fans_num' => $blogger->douyin_fans_num,//微博粉丝数
            'weibo_url'=> $blogger->weibo_url,//微博url
            'weibo_fans_num'=> $blogger->weibo_fans_num,//微博粉丝数
            'xiaohongshu_url'=> $blogger->xiaohongshu_url,//微博url
            'xiaohongshu_fans_num'=> $blogger->xiaohongshu_fans_num,//微博粉丝数
            'created_at'=> $blogger->created_at->toDateTimeString(),
            'last_follow_up_at' => $blogger->last_follow_up_at,
            'last_updated_user' => $blogger->last_updated_user,
            'last_updated_at' => $blogger->last_updated_at,
            'updated_at' => $blogger->updated_at->toDateTimeString(),
            'power' =>  $blogger->power,//对博主是否有编辑权限
            'powers' => $blogger->powers,
        ];

        if(!$setprivacy1 && $blogger ->creator_id != $user->id){
            if(empty($setprivacy1)){

//                   $array1['moduleable_id']= $project->id;
//                   $array1['moduleable_type']= ModuleableType::PROJECT;
//                   $array1['is_privacy']=  PrivacyType::OTHER;
//                   $setprivacy = PrivacyUser::where($array1)->groupby('moduleable_field')->get(['moduleable_field'])->toArray();
//                   foreach ($setprivacy as $key =>$v){
//                       $setprivacy1[]=array_values($v)[0];
//
//                   }
                $setprivacy1 =  PrivacyType::getBlogger();
            }
            foreach ($setprivacy1 as $key =>$v){
                $Viewprivacy2[$v]=$key;
            };
            $array = array_merge($array,$Viewprivacy2);
            foreach ($array as $key1 => $val1)
            {

                foreach ($Viewprivacy2 as $key2 => $val2)
                {

                    if($key1 === $key2 ){

                        $array[$key1] ='privacy';
                       // unset($array[$key1]);

                    }


                }
            }
        }
        $arraySimple = [
            'id' => hashid_encode($blogger->id),
            'nickname' => $blogger->nickname,
            'flag'   =>  ModuleableType::BLOGGER,
            'avatar' => $blogger->avatar,
            'status' => $blogger->sign_contract_status,
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
                    return $this->response->created();
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