<?php

namespace App\Http\Transformers;

use App\Models\Star;
use App\ModuleableType;
use App\TaskStatus;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StarTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    protected $availableIncludes = ['creator', 'tasks', 'trails','affixes','broker', 'publicity','project','works','calendar','schedule'];

    public function transform(Star $star)
    {
        //假头像
        $sub_str = substr($star->avatar,0,1);
        if ($sub_str == "#" || $sub_str == null){
            $star->avatar = "https://res-crm.papitube.com/image/artist-no-avatar.png";
        }
        $array = [
            'id' => hashid_encode($star->id),
            'name' => $star->name,
            'moduleable_type'   =>  ModuleableType::STAR,
            'desc' => $star->desc,
            'avatar' => $star->avatar,
            'gender' => $star->gender,
            'birthday' => $star->birthday,
            'phone' => $star->phone,
            'wechat' => $star->wechat,
            'email' => $star->email,
            'source' => $star->source,
            'communication_status' => $star->communication_status,
            'intention' => $star->intention,
            'intention_desc' => $star->intention_desc,
            'sign_contract_other' => $star->sign_contract_other,
            'sign_contract_other_name' => $star->sign_contract_other_name,
            'sign_contract_at' => $star->sign_contract_at,
            'sign_contract_status' => $star->sign_contract_status,
            'terminate_agreement_at' => $star->terminate_agreement_at,
            'status' => $star->status,
            'type' => $star->type,
            'created_at' => $star->created_at->formatLocalized('%Y-%m-%d %H:%I'),
            'updated_at' => $star->updated_at->formatLocalized('%Y-%m-%d %H:%I'),
            'deleted_at' => $star->deleted_at,

            'platform'  =>  $star->platform,
            'weibo_url' =>  $star->weibo_url,
            'weibo_fans_num'  =>  $star->weibo_fans_num,
            'baike_url' =>  $star->baike_url,
            'baike_fans_num'  =>  $star->baike_fans_num,
            'douyin_id' =>  $star->douyin_id,
            'douyin_fans_num' =>  $star->douyin_fans_num,
            'qita_url'  =>  $star->qita_url,
            'qita_fans_num' =>  $star->qita_fans_num,
            'artist_scout_name' =>  $star->artist_scout_name,
            'star_location' =>  $star->star_location,
            // 日志内容
            'last_updated_user' => $star->last_updated_user,
            'last_updated_at'   =>  $star->last_updated_at,
            'last_follow_up_at' => $star->last_follow_up_at,

        ];

        $arraySimple = [
            'id' => hashid_encode($star->id),
            'flag'   =>  ModuleableType::STAR,
            'name' => $star->name,
            'avatar' => $star->avatar,
            'status' =>$star->sign_contract_status
        ];

        return $this->isAll ? $array : $arraySimple;
    }

    public function includeCreator(Star $star)
    {
        $user = $star->creator;
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
        $tasks = $star->tasks()->where("status",TaskStatus::NORMAL)
            ->createDesc()->limit(5)->get();
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
        $trails = $star->trails;
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
    public function includeSchedule(Star $star)
    {

        $calendars = $star->calendar()->first();
        if($calendars){
            $calendar = $calendars->schedules()->select('*',DB::raw("ABS(NOW() - start_at)  AS diffTime")) ->orderBy('diffTime')->limit(3)->get();
            return $this->collection($calendar,new ScheduleTransformer());
        }else{
            return null;
        }
    }
}
