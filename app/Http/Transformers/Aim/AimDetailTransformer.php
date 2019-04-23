<?php

namespace App\Http\Transformers\Aim;

use App\Models\Aim;
use League\Fractal\TransformerAbstract;

class AimDetailTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['parents', 'children', 'relate_projects'];

    public function transform(Aim $aim)
    {
        $arr = [
            'id' => hashid_encode($aim->id),
            'title' => $aim->title,
            'range' => $aim->range,
            'department_id' => $aim->department_id ? hashid_encode($aim->department_id) : null,
            'department_name' => $aim->department_name,
            'period_id' => hashid_encode($aim->period_id),
            'period_name' => $aim->period_name,
            'principal_id' => hashid_encode($aim->principal_id),
            'principal_name' => $aim->principal_name,
            'type' => $aim->type,
            'amount_type' => $aim->amount_type,
            'amount' => $aim->amount,
            'position' => $aim->position,
            'talent_level' => $aim->talent_level,
            'aim_level' => $aim->aim_level,
            'deadline' => $aim->deadline,
            'status' => $aim->status,
            'percentage' => $aim->percentage,
            'desc' => $aim->desc,
            'created_at' => $aim->created_at->toDateString(),
            'updated_at' => $aim->updated_at->toDateString(),
        ];

        return $arr;
    }

    public function includeRelateProjects(Aim $aim)
    {
        $projects =$aim->projects;
        return $this->collection($projects, new AimProjectTransformer());
    }

    public function includeParents(Aim $aim)
    {
        $parents =$aim->parents;
        return $this->collection($parents, new AimParentTransformer());
    }

    public function includeChildren(Aim $aim)
    {
        $projects =$aim->children;
        return $this->collection($projects, new AimChildTransformer());
    }
}