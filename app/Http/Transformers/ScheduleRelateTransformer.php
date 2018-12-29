<?php

namespace App\Http\Transformers;

use App\Models\ScheduleRelate;
use League\Fractal\TransformerAbstract;

class ScheduleRelateTransformer extends TransformerAbstract
{

    public function transform(ScheduleRelate $scheduleRelate)
    {
        $array = [
            'id' => hashid_encode($scheduleRelate->id),
            'moduleable_id' =>hashid_encode($scheduleRelate->moduleable_id),
        ];

        return $array;
    }


}