<?php

namespace App\Http\Transformers;

use App\Models\Star;
use League\Fractal\TransformerAbstract;

class StarTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'broker'];

    public function transform(Star $star)
    {
        $array = [
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
            'intention' => boolval($star->intention),
            'intention_desc' => $star->intention_desc,
            'sign_contract_other' => boolval($star->sign_contract_other),
            'sign_contract_other_name' => $star->sign_contract_other_name,
            'sign_contract_at' => $star->sign_contract_at,
            'sign_contract_status' => $star->sign_contract_status,
            'terminate_agreement_at' => $star->terminate_agreement_at,
            'status' => $star->status,
            'type' => $star->type,
            'created_at' => $star->created_at->toDatetimeString(),
            'updated_at' => $star->updated_at->toDatetimeString(),
            'deleted_at' => $star->deleted_at,

        ];

        return $array;
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
        $user = $star->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeTasks(Star $star)
    {
        $tasks = $star->tasks()->createDesc()->get();
        return $this->collection($tasks, new TaskTransformer());
    }

    public function includeAffixes(Star $star)
    {
        $affixes = $star->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
}