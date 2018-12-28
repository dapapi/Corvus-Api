<?php

namespace App\Http\Transformers;

use App\Interfaces\ApprovalInstanceInterface;
use League\Fractal\TransformerAbstract;

class ApprovalInstanceTransformer extends TransformerAbstract
{
    public function transform(ApprovalInstanceInterface $instance)
    {
        $arr = [
            'title' => $instance->form->name,
            'form_instance_number' => $instance->form_instance_number,
        ];
        if ($instance->status) {
            $arr['form_status'] = $instance->status->id;
            $arr['form_status_name'] = $instance->status->name;
            $arr['form_status_icon'] = $instance->status->icon;
        }

        return $arr;
    }
}