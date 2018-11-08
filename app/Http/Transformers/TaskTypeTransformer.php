<?php

namespace App\Http\Transformers;

use App\Models\TaskType;
use League\Fractal\TransformerAbstract;

class TaskTypeTransformer extends TransformerAbstract
{

    public function transform(TaskType $taskType)
    {
        return [
            'id' => hashid_encode($taskType->id),
            'title' => $taskType->title
        ];
    }

}
