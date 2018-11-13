<?php

namespace App\Http\Transformers;

use App\Models\Blogger;
use League\Fractal\TransformerAbstract;

class BloggerTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'producer', 'type'];

    public function transform(Blogger $blogger)
    {
        return [
            'id' => hashid_encode($blogger->id),
            'nickname' => $blogger->nickname,
            'communication_status' => $blogger->communication_status,//沟通状态
            'intention' => boolval($blogger->intention),//与我司签约意向
            'intention_desc' => $blogger->intention_desc,//不与我司签约原因
            'sign_contract_at' => $blogger->sign_contract_at,//签约日期
            'level' => $blogger->level,//博主级别
            'hatch_star_at' => $blogger->hatch_star_at,//孵化期开始时间
            'hatch_end_at' => $blogger->hatch_star_at,//孵化期结束时间
            'sign_contract_status' => $blogger->sign_contract_status,//签约状态
            'desc' => $blogger->desc,//描述/备注
            'type' => $blogger->type,
            'status' => $blogger->status,
            'avatar' => $blogger->avatar,
            'gender' => $blogger->gender,
            'cooperation_demand' => $blogger->cooperation_demand,//合作需求
            'terminate_agreement_at' => $blogger->terminate_agreement_at,//解约日期
            'sign_contract_other' => $blogger->sign_contract_other,//是否签约其他公司
            'sign_contract_other_name' => $blogger->sign_contract_other_name,//签约公司名称
        ];
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

    public function includeTasks(Blogger $blogger)
    {
        $tasks = $blogger->tasks()->createDesc()->get();
        return $this->collection($tasks, new TaskTransformer());
    }

    public function includeAffixes(Blogger $blogger)
    {
        $affixes = $blogger->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }

}