<?php

namespace App\Http\Transformers;

use App\Models\Dashboard;
use App\Models\Task;
use App\TaskStatus;
use Carbon\Carbon;
use League\Fractal\ParamBag;
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

    private $validParams = ['days'];

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

    # todo 改为列表,用新的transformer
    public function includeTasks(Dashboard $dashboard, ParamBag $params)
    {
        $userIds = $dashboard->department->users()->pluck('user_id');

        $tasksBuilder = Task::whereIn('principal_id', $userIds);

        $usedParams = array_keys(iterator_to_array($params));
        if ($invalidParams = array_diff($usedParams, $this->validParams)) {
            throw new \Exception(sprintf(
                'Invalid param(s): "%s". Valid param(s): "%s"',
                implode(',', $usedParams),
                implode(',', $this->validParams)
            ));
        }

        // Processing
        $days = $params->get('days');

        $count = $tasksBuilder->count('id');
        $delayCount = $tasksBuilder->where('status', TaskStatus::DELAY)->count('id');

        # todo 时间可以参数输入
        $timePoint = Carbon::today('PRC')->subDays($days);
        $newTasks = $tasksBuilder->where('created_at', '>', $timePoint)->count('id');

        $taskInfoArr = [
            'total' => $count,
            'delayCount' => $delayCount,
            'latest' => $newTasks,
        ];

        return $this->item($taskInfoArr, function($arr) {
            return $arr;
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