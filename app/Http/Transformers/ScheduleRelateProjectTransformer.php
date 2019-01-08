<?php

namespace App\Http\Transformers;

use App\Models\ScheduleRelate;
use League\Fractal\TransformerAbstract;

class ScheduleRelateProjectTransformer extends TransformerAbstract
{

    public function transform(ScheduleRelate $scheduleRelate)
    {

         if($scheduleRelate->projectid() == null){

             return array();
         }else{
        $array = [

            'moduleable_id' => hashid_decode($scheduleRelate->moduleable_id),
            'title' => $scheduleRelate->projecttitle()
        ];

        return $array;
         }
    }

}