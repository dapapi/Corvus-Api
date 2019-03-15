<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\StoreDashboardRequest;
use App\Http\Transformers\DashboardTransformer;
use App\Models\Dashboard;
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
        $collection = Dashboard::get();
        return $this->response->collection($collection, new DashboardTransformer());
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
        DB::beginTransaction();
        try {
            $dashboard = Dashboard::create($payload);
            $dashboard->relate->create([
                # 暂时写死,需要之后修改
                'includes' => 'aims,tasks,projects,clients,stars'
            ]);
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

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
