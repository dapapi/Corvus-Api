<?php

namespace App\Http\Transformers;

use App\Models\Blogger;
use League\Fractal\TransformerAbstract;

class BloggerTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'producer', 'type','producer','project',];

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Blogger $blogger)
    {
        $array = [
            'id' => hashid_encode($blogger->id),
            'nickname' => $blogger->nickname,
            'platform_id' => $blogger->platform_id,    //平台
            'avatar' => $blogger->avatar,
            'communication_status' => $blogger->communication_status,//沟通状态
            'intention' => boolval($blogger->intention),//与我司签约意向
            'intention_desc' => $blogger->intention_desc,//不与我司签约原因
            'sign_contract_at' => $blogger->sign_contract_at,//签约日期
            'level' => $blogger->level,//博主级别
            'hatch_star_at' => $blogger->hatch_star_at,//孵化期开始时间
            'hatch_end_at' => $blogger->hatch_star_at,//孵化期结束时间
            'sign_contract_status' => $blogger->sign_contract_status,//签约状态
            'icon' => $blogger->icon, // 头像
            'desc' => $blogger->desc,//描述/备注
            'type' => $blogger->type,
            'gender' => $blogger->gender,
            'cooperation_demand' => $blogger->cooperation_demand,//合作需求
            'terminate_agreement_at' => $blogger->terminate_agreement_at,//解约日期
            'sign_contract_other' => $blogger->sign_contract_other,//是否签约其他公司
            'sign_contract_other_name' => $blogger->sign_contract_other_name,//签约公司名称
            'status' => $blogger->status,
            'created_at'=> $blogger->created_at,
            'updated_at' => $blogger->updated_at

        ];
        $arraySimple = [
            'id' => hashid_encode($blogger->id),
            'nickname' => $blogger->nickname,
            'avatar' => $blogger->avatar
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
        $tasks = $blogger->tasks()->createDesc()->get();
        return $this->collection($tasks, new TaskTransformer());
    }
    public function includeProject(Blogger $blogger)
    {
        $tasks = $blogger->project()->createDesc()->get();
        return $this->collection($tasks, new ProjectTransformer());
    }
    public function includeAffixes(Blogger $blogger)
    {
        $affixes = $blogger->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }

}