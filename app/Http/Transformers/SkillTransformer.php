<?php

namespace App\Http\Transformers;

use App\Models\PersonalSkills;
use League\Fractal\TransformerAbstract;

class SkillTransformer extends TransformerAbstract
{
    public function transform(PersonalSkills $skill)
    {
        return [
            'id' => hashid_encode($skill->id),
            'language_level' => $skill->language_level,
            'certificate' => $skill->certificate,
            'computer_level' => $skill->computer_level,
            'specialty' => $skill->specialty,
            'disease' => $skill->disease,
            'pregnancy' => $skill->pregnancy,
            'migration' => $skill->migration,
            'remark' => $skill->remark,

        ];
    }
}