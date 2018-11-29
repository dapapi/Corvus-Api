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
            'start_time' => $edu->start_time,
            'end_time' => $edu->end_time,
            'degree' => $edu->degree,
            'graduate' => $edu->graduate,
        ];


    }
}