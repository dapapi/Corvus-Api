<?php

namespace App\Http\Transformers\Aim;

use App\Models\AimParent;
use League\Fractal\TransformerAbstract;

class AimChildTransformer extends TransformerAbstract
{
    public function transform(AimParent $aim)
    {
        return [
            'id' => hashid_encode($aim->c_aim_id),
            'name' => $aim->c_aim_name,
            'range' => $aim->c_aim_range,
        ];
    }
}