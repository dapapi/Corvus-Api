<?php

namespace App\Http\Transformers;

use App\Models\Blogger;
use League\Fractal\TransformerAbstract;

class BloggerListTransformer extends TransformerAbstract
{
    public function transform(Blogger $blogger)
    {
        return [
            'id'    =>  hashid_encode($blogger->id),
            'nickname'  => $blogger->nickname,
            'type'  =>  $blogger->type,
            'sign_contract_status'  =>  $blogger->sign_contract_status,
            'weibo_fans_num'    =>  $blogger->weibo_fans_num,
            'sign_contract_at'  =>  $blogger->sign_contract_at,
            'terminate_agreement_at'    =>  $blogger->terminate_agreement_at,
            'created_at'    =>  $blogger->created_at->toDateTimeString(),
            'last_follow_up_at' =>  $blogger->follow_up_at,
            'communication_status'  =>  $blogger->communication_status,
            'publicity_user_names'  =>  explode(",",$blogger->publicity_user_names),
        ];
    }
}