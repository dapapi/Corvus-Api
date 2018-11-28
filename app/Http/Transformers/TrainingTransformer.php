<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Training;


class TrainingTransformer extends TransformerAbstract
{
    public function transform(Training $training)
    {
        return [
            'id' => hashid_encode($training->id),
            'user_id' => $training->user_id,
            'course_name' => $training->course_name,
            'certificate' => $training->certificate,
            'address' => $training->address,
            'trained_time' => $training->trained_time,

        ];


    }
}