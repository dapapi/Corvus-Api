<?php

namespace App\Http\Transformers\Aim;

use App\Models\AimParent;
use League\Fractal\TransformerAbstract;

class AimParentTransformer extends TransformerAbstract
{
    public function transform(AimParent $aim)
    {
        return [
            'id' => hashid_encode($aim->p_aim_id),
            'name' => $aim->p_aim_name,
            'range' => $aim->p_aim_range,
        ];
    }
}