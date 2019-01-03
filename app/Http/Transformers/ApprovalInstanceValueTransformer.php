<?php

namespace App\Http\Transformers;

use App\Models\ApprovalForm\InstanceValue;
use League\Fractal\TransformerAbstract;

class ApprovalInstanceValueTransformer extends TransformerAbstract
{
    public function transform(InstanceValue $value)
    {
        return [
            'value' => $value->form_control_value
        ];
    }
}