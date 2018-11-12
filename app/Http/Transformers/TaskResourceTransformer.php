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
            case ModuleableType::STAR:
                return $this->item($resourceable, new StarTransformer());
            case ModuleableType::CLIENT:
                return $this->item($resourceable, new ClientTransformer());
            case ModuleableType::TRAIL:
                return $this->item($resourceable, new TrailTransformer());
            case ModuleableType::BLOGGER:
                return $this->item($resourceable, new BloggerTransformer());
            //TODO
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
