<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;

class ContractApprovalTransformer extends TransformerAbstract
{
    public function transform(Object $obj)
    {
        return [
            'business_type' => $obj->business_type,
            'client_id' => $obj->client_id ? hashid_encode($obj->client_id) : null,
            'contract_end_date' => $obj->contract_end_date,
            'contract_money' => $obj->contract_money,
            'contract_number' => $obj->contract_number,
            'contract_sharing_ratio' => $obj->contract_sharing_ratio,
            'contract_start_date' => $obj->contract_start_date,
            'created_at' => $obj->created_at,
            'creator_id' => $obj->creator_id ? hashid_encode($obj->creator_id) : null,
            'creator_name' => $obj->creator_name,
            'form_id' => hashid_encode($obj->form_id),
            'form_instance_number' => $obj->form_instance_number,
            'form_status' => $obj->form_status,
            'id' => hashid_encode($obj->id),
            'name' => $obj->name,
        ];
    }
}