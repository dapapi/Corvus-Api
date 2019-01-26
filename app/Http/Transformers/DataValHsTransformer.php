<?php

namespace App\Http\Transformers;

use App\Models\DataDictionary;
use League\Fractal\TransformerAbstract;

class DataValHsTransformer extends TransformerAbstract
{

    //protected $availableIncludes = ['creator', 'pTask', 'tasks', 'resource', 'affixes', 'participants', 'type','operateLogs'];

   // protected $defaultIncludes = ['dataDictionaries'];

    public function transform(DataDictionary $dataDictionary)
    {
        $array = [
            'val' => hashid_encode($dataDictionary->user_id),
            'name' => $dataDictionary->enum_value


        ];

//        $array['task_p'] = true;
//        if ($task->task_pid) {
//            $array['task_p'] = false;
//        }

        return $array;
    }

}
