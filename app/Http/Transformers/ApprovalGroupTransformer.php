<?php

namespace App\Http\Transformers;

use App\Models\ApprovalGroup;
use League\Fractal\TransformerAbstract;

class ApprovalGroupTransformer extends TransformerAbstract
{
    public function transform(ApprovalGroup $group)
    {
        return [
            'id' => hashid_encode($group->id),
            'name' => $group->name,
            'desc' => $group->desc,
        ];
    }
}