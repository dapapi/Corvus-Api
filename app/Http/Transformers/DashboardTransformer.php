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
            'name' => $department->name . '仪表盘',
            'department_name' => $department->name,
        ];

        return $arr;
    }
}