<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Education;


class EducationTransformer extends TransformerAbstract
{
    public function transform(Education $edu)
    {
        return [
            'id' => hashid_encode($edu->id),
            'user_id' => $edu->user_id,
            'school' => $edu->school,
            'specialty' => $edu->specialty,
            'start_time' => $edu->start_time->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,
            'end_time' => $edu->end_time->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,
            'degree' => $edu->degree,
            'graduate' => $edu->graduate,
        ];


    }
}