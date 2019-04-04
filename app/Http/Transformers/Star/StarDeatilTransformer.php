<?php

namespace App\Http\Transformers;

use App\Models\Star;
use App\ModuleableType;
use App\ModuleUserType;
use App\TaskStatus;
use App\User;
use Illuminate\Support\Facades\DB;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;
class StarDeatilTransformer extends TransformerAbstract
{
    public function transform(Star $star)
    {
        return [
            'id' => hashid_encode($star->id),
            'name' => $star->name,
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

            'created_at' => $star->created_at->toDatetimeString(),
//            'created_at' => $star->created_at,
            'updated_at' => $star->updated_at->toDatetimeString(),
//            'updated_at' => $star->updated_at,
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
//            'last_updated_user' => $star->getLastUpdatedUserAttribute(),
            'last_updated_at'   =>  $star->last_updated_at,
//            'last_updated_at'   =>  $star->getLastUpdatedAtAttribute(),
//            'last_updated_at'   =>  $star->last_updated_at,
//            'last_follow_up_at' => $star->getLastFollowUpAtAttribute(),
            'last_follow_up_at' =>  $star->last_follow_up_at,
            'star_risk_point'   =>  $star->star_risk_point,
            'power' =>  $star->power,
            'powers'    =>  $star->powers,
            'tasks' => $this->getTasks($star),
            'affixes'   =>  $this->includeAffixes($star),
            'creator'   =>  $this->getCreator($star),
            'broker'    =>  $this->getBroker($star),
            'publicity' =>  $this->getPublicity($star),
        ];
    }
    public function getCreator(Star $star)
    {
        $user = $star->creator()->select('id','name','avatar')->first();
        $department = $user->department()->value('name') ;
        $user->department = $department;
        return $user;
//        return $this->item($user,new UserTransformer());
    }
    public function includeAffixes(Star $star)
    {
        $affixes = $star->affixes()->createDesc()->get();
        return ['data'=>$affixes];
    }
    public function getPublicity(Star $star){
        $users = $star->publicity()->select('users.id','users.name','avatar')->get();
        foreach ($users as $user){
            $department = $user->department()->value('name') ;
            $user->id = hashid_encode($user->id);
            $user->department = $department;
        }
        return $users;
    }
    public function getBroker(Star $star)
    {
        $users = $star->broker()->select('users.id','users.name','avatar')->get();
        foreach ($users as $user){
            $department = $user->department()->value('name') ;
            $user->id = hashid_encode($user->id);
            $user->department = $department;
        }
        return $users;
    }
    public function getTasks(Star $star)
    {
        $tasks = $star->tasks()->select('tasks.id','tasks.status','tasks.title','tasks.end_at',DB::raw('users.name as principal_name'))->stopAsc()
            ->LeftJoin('users','tasks.principal_id','users.id')
            ->where('tasks.status',TaskStatus::NORMAL)->searchData()
            ->limit(3)->get();
        foreach ($tasks as $task){
            $tasks->id = hashid_encode($task->id);
        }
        return $tasks;
    }

}

