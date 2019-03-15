<?php

namespace App\Http\Transformers;

use App\Models\Dashboard;
use App\Models\Task;
use App\TaskStatus;
use Carbon\Carbon;
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
            'name' => $dashboard->name,
            'includes'=> $dashboard->includes,
            'department' => $dashboard->department->name,
            'department_id' => hashid_encode($dashboard->department->id),
        ];

        return $arr;
    }

    public function includeTask(Dashboard $dashboard)
    {
        $userIds = $dashboard->department->users()->pluck('user_id');
        $tasksBuilder = Task::whereIn('principal_id', $userIds);
        $count = $tasksBuilder->count('id');
        $delayCount = $tasksBuilder->where('status', TaskStatus::DELAY)->count('id');

        # todo 时间可以参数输入
        $timePoint = Carbon::today('PRC')->subDays(7);
        $newTasks = $tasksBuilder->where('created_at', '>', $timePoint)->count('id');

        $taskInfoArr = [
            'total' => $count,
            'delayCount' => $delayCount,
            'latest' => $newTasks,
        ];

        return $this->item($taskInfoArr, function($arr) {
            return [
                'test' => $arr['test'],
            ];
        });
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