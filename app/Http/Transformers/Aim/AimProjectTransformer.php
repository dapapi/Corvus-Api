<?php

namespace App\Http\Transformers\Aim;

use App\Models\AimProject;
use League\Fractal\TransformerAbstract;

class AimProjectTransformer extends TransformerAbstract
{
    public function transform(AimProject $project)
    {
        return [
            'id' => hashid_encode($project->project_id),
            'title' => $project->project_name,
        ];
    }
}