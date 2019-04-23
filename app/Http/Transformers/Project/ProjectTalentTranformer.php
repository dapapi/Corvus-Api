<?php

namespace App\Http\Transformers\Project;

use App\Models\ProjectTalent;
use League\Fractal\TransformerAbstract;

class ProjectTalentTranformer extends TransformerAbstract
{
    public function transform(ProjectTalent $talent)
    {
        return [
            'id' => hashid_encode($talent->talent_id),
            'name' => $talent->talent_name,
            'moduleable_type' => $talent->talent_type,
        ];
    }
}