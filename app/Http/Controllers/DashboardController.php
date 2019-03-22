<?php

namespace App\Http\Controllers;

use App\Helper\Common;
use App\Http\Requests\Dashboard\StoreDashboardRequest;
use App\Http\Transformers\DashboardTransformer;
use App\Models\Dashboard;
use App\Models\Department;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * 左边栏接口
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();
        $departmentId = $user->department()->first()->id;
        $departmentArr = Common::getChildDepartment($departmentId);
        # 149 总裁办 硬编码去掉
        $departments = Department::whereIn('id', $departmentArr)->where('id', '!=', 149)->get();
        return $this->response->collection($departments, new DashboardTransformer());
    }

    /**
     * 新建
     * @param StoreDashboardRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(StoreDashboardRequest $request)
    {
        $payload = $request->all();
        $payload['department_id'] = hashid_decode($payload['department_id']);

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;
        $payload['includes'] = 'aims,tasks,projects,clients,stars';
        try {
            $dashboard = Dashboard::create($payload);
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('创建失败');
        }
        return $this->response->item($dashboard, new DashboardTransformer());
    }

    /**
     * 编辑用详情
     * transformer 带出每个部分内容
     * @param Request $request
     * @param Dashboard $dashboard
     * @return \Dingo\Api\Http\Response
     */
    public function detail(Request $request, Dashboard $dashboard)
    {
        return $this->response->item($dashboard, new DashboardTransformer());
    }

    /**
     * 编辑
     * @param Request $request
     * @param Dashboard $dashboard
     * @return \Dingo\Api\Http\Response
     */
    public function edit(Request $request, Dashboard $dashboard)
    {
        $payload = $request->all();
        $dashboard->update($payload);
        return $this->response->accepted(null, '更新成功');
    }

    /**
     * 删除
     */
    public function delete()
    {

    }
}
