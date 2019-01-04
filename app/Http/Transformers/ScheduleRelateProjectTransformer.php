<?php

namespace App\Http\Transformers;

use App\Models\ScheduleRelate;
use League\Fractal\TransformerAbstract;

class ScheduleRelateProjectTransformer extends TransformerAbstract
{

    public function transform(ScheduleRelate $scheduleRelate)
    {
        $array = [

            'moduleable_id' =>hashid_encode($scheduleRelate->moduleable_id),
            'title' => $scheduleRelate->projecttitle()
        ];

        return $array;
    }

}