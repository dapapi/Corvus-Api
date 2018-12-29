<?php

namespace App\Http\Transformers;

use App\Interfaces\ApprovalInstanceInterface;
use App\Models\ApprovalFlow\Change;
use League\Fractal\TransformerAbstract;

class ApprovalInstanceTransformer extends TransformerAbstract
{
    public function transform(ApprovalInstanceInterface $instance)
    {
        $count = Change::where('form_instance_number', $instance->form_instance_number)->count('form_instance_number');
        $arr = [
            'title' => $instance->form->name,
            'form_instance_number' => $instance->form_instance_number,
        ];

        if ($count > 1)
            $array['approval_begin'] = 1;
        else
            $array['approval_begin'] = 0;


        if ($instance->status) {
            $arr['form_status'] = $instance->status->id;
            $arr['approval_status_name'] = $instance->status->name;
            $arr['approval_status_icon'] = $instance->status->icon;
        }

        return $arr;
    }
}