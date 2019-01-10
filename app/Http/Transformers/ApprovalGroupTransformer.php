<?php

namespace App\Http\Transformers;

use App\Models\ApprovalGroup;
use League\Fractal\TransformerAbstract;

class ApprovalGroupTransformer extends TransformerAbstract
{
    protected $defaultIncludes = ['forms'];

    public function transform(ApprovalGroup $group)
    {
        return [
            'id' => hashid_encode($group->id),
            'name' => $group->name,
            'desc' => $group->description,
        ];
    }

    public function includeForms(ApprovalGroup $group)
    {
        $forms = $group->forms;
        return $this->collection($forms, new ApprovalFormTransformer());
    }
}