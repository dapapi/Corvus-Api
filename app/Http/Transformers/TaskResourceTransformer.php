<?php

namespace App\Http\Transformers;

use App\Models\Project;
use App\Models\TaskResource;
use App\ModuleableType;
use League\Fractal\TransformerAbstract;

class TaskResourceTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['resourceable', 'resource'];

    public function transform(TaskResource $taskResource)
    {
        return [
//            'task_id' => $taskResource->task_id,
//            'resourceable_id',
//            'resourceable_type',
//            'resource_id',
        ];
    }

    public function includeResourceable(TaskResource $taskResource)
    {
        $resourceable = $taskResource->resourceable;
        if (!$resourceable)
            return null;
        switch ($taskResource->resourceable_type) {
            case ModuleableType::PROJECT:
                return $this->item($resourceable, new ProjectTransformer());
            case ModuleableType::TASK:
                return $this->item($resourceable, new TaskTransformer());
        }
    }

    public function includeResource(TaskResource $taskResource)
    {
        $resource = $taskResource->resource;
        if (!$resource)
            return null;
        return $this->item($resource, new ResourceTransformer());
    }
}
