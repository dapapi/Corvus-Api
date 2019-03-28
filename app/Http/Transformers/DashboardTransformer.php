<?php

namespace App\Http\Transformers;

use App\Models\Department;
use League\Fractal\TransformerAbstract;

class DashboardTransformer extends TransformerAbstract
{

    public function transform(Department $department)
    {
        $arr = [
            'id' => hashid_encode($department->id),
            'department_name' => $department->name,
            'name' => $department->name . '仪表盘',
        ];

        return $arr;
    }
}