<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;

class GeneralApprovalTransformer extends TransformerAbstract
{
    public function transform(Object $obj)
    {
        return [
            'form_instance_id' => hashid_encode($obj->form_instance_id),
            'form_id' => hashid_encode($obj->form_id),
            'form_instance_number' => $obj->form_instance_number,
            'apply_id' => hashid_encode($obj->apply_id),
            'form_status' => $obj->form_status,
            'created_by' => $obj->created_by,
            'created_at' => $obj->created_at,
            'name' => $obj->name,
            'group_name' => $obj->group_name,
            'group_id' => hashid_encode($obj->group_id),
            'icon_url'  =>  $obj->icon_url,
            'approval_status_name'  =>  $obj->approval_status_name,
            'icon'  =>  $obj->icon
        ];
    }
}