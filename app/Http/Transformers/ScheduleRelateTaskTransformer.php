<?php

namespace App\Http\Transformers;

use App\Models\ScheduleRelate;
use League\Fractal\TransformerAbstract;

class ScheduleRelateTaskTransformer extends TransformerAbstract
{
    //protected $defaultIncludes = ['task'];

    public function transform(ScheduleRelate $scheduleRelate)
    {
        if($scheduleRelate->taskid() == null){

            return array();
        }else{

            $array = [
            'moduleable_id' => hashid_encode($scheduleRelate->moduleable_id),
            'title' => $scheduleRelate->tasktitle()
        ];
        return $array;
    }}
}