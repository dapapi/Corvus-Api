<?php

namespace App\Http\Transformers;

use App\Models\Star;
use League\Fractal\TransformerAbstract;

class StarListTransformer extends TransformerAbstract
{
    public function transform(Star $star)
    {
        return [
            'id'    =>  hashid_encode($star->id),
            'name' => $star->name,
            'avatar' => $star->avatar,
            'weibo_fans_num'    => $star->weibo_fans_num,
            'source' => $star->source,
            'created_at' => $star->created_at->toDatetimeString(),
            'last_follow_up_at' =>  $star->follow_up_at,
            'sign_contract_at'  => $star->sign_contract_at,
            'terminate_agreement_at'    =>  $star->terminate_agreement_at,
            'sign_contract_status'  =>  $star->sign_contract_status,
            'communication_status'  =>  $star->communication_status,
            'birthday'  =>  $star->birthday,
        ];

    }
//    public function includeContracts(Star $star)
//    {
//
//        $contracts = $star->contracts()
//            ->leftJoin('approval_form_business','approval_form_business.form_instance_number','contracts.form_instance_number')
//            ->where('form_id',7)
//            ->select('contract_start_date')->first();
//        if($contracts){
//            return $this->item($contracts, new ContractDateTransformer(false));
//        }else{
//            return $this->null();
//        }
//    }
}