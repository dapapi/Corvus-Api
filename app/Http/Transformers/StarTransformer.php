<?php

namespace App\Http\Transformers;

use App\Models\Star;
use League\Fractal\TransformerAbstract;

class StarTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['creator', 'tasks', 'affixes'];

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
            'contract_type' => $star->contract_type,
            'divide_into_proportion' => $star->divide_into_proportion,
            'terminate_agreement_at' => $star->terminate_agreement_at,
            'status' => $star->status,
            'type' => $star->type,
            'created_at' => $star->created_at->toDatetimeString(),
            'updated_at' => $star->updated_at->toDatetimeString(),
            'deleted_at' => $star->deleted_at,
        ];

        return $array;
    }
}