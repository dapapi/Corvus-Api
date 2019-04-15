<?php

namespace App\Http\Transformers\Aim;

use App\Models\Aim;
use League\Fractal\TransformerAbstract;

class AimSimpleTransformer extends TransformerAbstract
{
    public function transform(Aim $aim)
    {
        $arr = [
            'id' => hashid_encode($aim->id),
            'title' => $aim->title,
            'period_id' => hashid_encode($aim->period_id),
            'principal_id' => hashid_encode($aim->principal_id),
            'principal_name'=> $aim->principal_name,
            'deadline' => $aim->deadline,
            'percentage' => $aim->percentage,
        ];

        return $arr;
    }
}