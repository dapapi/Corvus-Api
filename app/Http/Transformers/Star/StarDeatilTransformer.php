<?php

namespace App\Http\Transformers;

use App\Models\Star;
use App\TaskStatus;
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
            'tasks' => $this->includeTasks($star),
            'affixes'   =>  $this->includeAffixes($star),
            'creator'   =>  $this->getCreator($star),
            'broker'    =>  $this->includeBroker($star),
            'publicity' =>  $this->includePublicity($star),
        ];
    }
    public function getCreator(Star $star)
    {
        $user = $star->creator()->select('name')->first();
        return ['data'=>$user];
//        return $this->item($user, new UserTransformer());
    }
    public function includeAffixes(Star $star)
    {
        $affixes = $star->affixes()->createDesc()->get();
        return ['data'=>$affixes];
//        return $this->collection($affixes, new AffixTransformer());
    }
    public function includePublicity(Star $star){
        $users = $star->publicity()->select('name')->get();
        return ['data'=>$users];
//        return $this->collection($users,new UserTransformer());
    }
    public function includeBroker(Star $star)
    {
        $users = $star->broker()->select('name')->get();
        return ['data'=>$users];
//        return $this->collection($users, new UserTransformer());
    }
    public function includeTasks(Star $star)
    {
        $tasks = $star->tasks()->stopAsc()
            ->where('status',TaskStatus::NORMAL)->searchData()
            ->limit(3)->get();
        return $this->collection($tasks, new TaskTransformer());
    }

}