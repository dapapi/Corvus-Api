<?php

namespace App\Http\Transformers;

use App\Models\Project;
use League\Fractal\TransformerAbstract;

class simpleProjectTransformer extends TransformerAbstract
{
    public function transform(Project $project)
    {
        return [
            'id'    =>  $project->id,
            'title' =>  $project->title
        ];
    }
}