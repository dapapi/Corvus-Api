<?php

namespace App\Http\Transformers;

use App\Models\Project;
use League\Fractal\TransformerAbstract;

class ProjectTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'creator'];
    protected $defaultIncludes = ['fields'];

    public function transform(Project $project)
    {
        return [
            'id' => hashid_encode($project->id),
            'title' => $project->title,
            'privacy' => $project->privacy,
            'priority' => $project->priority,
        ];
    }

    public function includePrincipal(Project $project)
    {
        $principal = $project->principal;
        return $this->item($principal, new UserTransformer());
    }

    public function includeCreator(Project $project)
    {
        $creator = $project->creator;
        return $this->item($creator, new UserTransformer());
    }

    public function includeFields(Project $project)
    {
        $fields = $project->fields;

        return $this->collection($fields, new FieldValueTransformer());
    }
}
