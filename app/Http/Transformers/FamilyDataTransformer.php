<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\FamilyData;


class FamilyDataTransformer extends TransformerAbstract
{
    public function transform(FamilyData $familyData)
    {
        return [
            'id' => hashid_encode($familyData->id),
            'user_id' => $familyData->user_id,
            'name' => $familyData->name,
            'relation' => $familyData->relation,
            'birth_time' => $familyData->birth_time,
            'work_units' => $familyData->work_units,
            'position' => $familyData->position,
            'contact_phone' => $familyData->contact_phone,


        ];


    }
}