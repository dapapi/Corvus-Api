<?php

namespace App\Http\Transformers;

use App\Models\Dashboard;
use League\Fractal\TransformerAbstract;

class DashboardTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'task',
        'aim',
        'project',
        'client',
        'star'
    ];

    public function transform(Dashboard $dashboard)
    {
        $arr = [
            'name' => '仪表盘名称'
        ];

        return $arr;
    }

    public function includeTask(Dashboard $dashboard)
    {
        $department = $dashboard->department;
    }

    public function includeAim(Dashboard $dashboard)
    {

    }

    public function includeProject(Dashboard $dashboard)
    {

    }

    public function includeClient(Dashboard $dashboard)
    {

    }

    public function includeStar(Dashboard $dashboard)
    {

    }
}