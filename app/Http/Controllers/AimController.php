<?php

namespace App\Http\Controllers;

use App\Events\AimDataChangeEvent;
use App\Http\Requests\Aim\AimEditRequest;
use App\Http\Requests\Aim\AimStoreRequest;
use App\Http\Transformers\Aim\AimDetailTransformer;
use App\Http\Transformers\Aim\AimSimpleTransformer;

use App\Models\Aim;
use App\Models\OperateEntity;
use App\Models\Project;
use App\OperateLogMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AimController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->get('page_size', 10);
        # 进行中|已结束 切换
        $status = $request->get('status', 0);

        $query = Aim::where('status', $status);

        # tab部分
        $tab = $request->get('tab', 1);
        switch ($tab) {
            case 4: # 全部
                break;
            case 3: # 公司
                $query->where('range', Aim::RANGE_COMPANY);
                break;
            case 2: # 部门
                $user = Auth::guard('api')->user();
                $departmentId = DB::table('department_user')->where('user_id', $user->id)->value('department_id');
                $query->where('department_id', $departmentId)->where('range', Aim::RANGE_DEPARTMENT);
                break;
            case 1: # 个人
            default:
                $user = Auth::guard('api')->user();
                $query->where('principal_id', $user->id)->where('range', Aim::RANGE_PERSONAL);
                break;
        }

        # 周期切换
        if ($request->has('period_id') && $request->get('period_id')) {
            $periodId = hashid_decode($request->get('period_id'));
            $query->where('period_id', $periodId);
        }

        # 筛选部门
        if ($request->has('department_id') && $request->get('department_id')) {
            $departmentId = hashid_decode($request->get('department_id'));
            $query->where('department_id', $departmentId);
        }

        # 选择负责人
        if ($request->has('principal_id') && $request->get('principal_id')) {
            $principalId = hashid_decode($request->get('principal_id'));
            $query->where('principal_id', $principalId);
        }

        # 选择目标范围
        if ($request->has('range') && $request->get('range')) {
            $range = $request->get('range');
            $query->where('range', $range);
        }

        # 选择输入目标名称
        if ($request->has('keyword') && $request->get('keyword')) {
            $keyword = $request->get('keyword');
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        $paginator = $query->orderBy('last_follow_up_at', 'desc')->paginate($pageSize);
        return $this->response->paginator($paginator, new AimSimpleTransformer());
    }

    public function store(AimStoreRequest $request)
    {
        $payload = $request->all();
        if ($request->has('department_id')) {
            $payload['department_id'] = hashid_decode($payload['department_id']);
            $payload['department_name'] = DB::table('departments')->where('id', $payload['department_id'])->value('name');
        }

        $payload['principal_id'] = hashid_decode($payload['principal']['id']);
        $payload['principal_name'] = $payload['principal']['name'];

        $payload['period_id'] = hashid_decode($payload['period_id']);
        $payload['period_name'] = DB::table('aim_periods')->where('id', $payload['period_id'])->value('name');
        $payload['deadline'] = DB::table('aim_periods')->where('id', $payload['period_id'])->value('end_at');

        $creator = Auth::guard('api')->user();
        $payload['creator_id'] = $creator->id;
        $payload['creator_name'] = $creator->name;
        $payload['last_follow_up_at'] = Carbon::now()->toDateTimeString();

        DB::beginTransaction();
        try {
            $aim = Aim::create($payload);
            if ($request->has('parents_ids')) {
                foreach ($payload['parents_ids'] as $id) {
                    $id = hashid_decode($id);
                    $pAim = Aim::find($id);
                    if ($pAim)
                        $aim->parents()->create([
                            'p_aim_id' => $pAim->id,
                            'p_aim_name' => $pAim->title,
                            'p_aim_range' => $pAim->range,
                            'c_aim_id' => $aim->id,
                            'c_aim_name' => $aim->title,
                            'c_aim_range' => $aim->range,
                        ]);
                }
            }
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $aim,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->error('创建失败');
        }
        DB::commit();

        return $this->response->item($aim, new AimDetailTransformer());

    }

    public function detail(Request $request, Aim $aim)
    {
        return $this->response->item($aim, new AimDetailTransformer());
    }

    public function edit(AimEditRequest $request, Aim $aim)
    {
        $payload = $request->all();
        $oldModel = clone $aim;
        if ($request->has('department_id')) {
            $payload['department_id'] = hashid_decode($payload['department_id']);
            $payload['department_name'] = DB::table('departments')->where('id', $payload['department_id'])->value('name');
        }

        if ($request->has('principal')) {
            $payload['principal_id'] = hashid_decode($payload['principal']['id']);
            $payload['principal_name'] = $payload['principal']['name'];
        }

        if ($request->has('period_id')) {
            $payload['period_id'] = hashid_decode($payload['period_id']);
            $payload['period_name'] = DB::table('aim_periods')->where('id', $payload['period_id'])->value('name');
            $payload['deadline'] = DB::table('aim_periods')->where('id', $payload['period_id'])->value('end_at');
        }

        DB::beginTransaction();
        try {
            $aim->update($payload);
            if ($request->has('parents_ids')) {
                $aim->parents->delete();
                foreach ($payload['parents_ids'] as $id) {
                    $id = hashid_decode($id);
                    $pAim = Aim::find($id);
                    if ($pAim)
                        $aim->parents()->create([
                            'p_aim_id' => $pAim->id,
                            'p_aim_name' => $pAim->title,
                            'p_aim_range' => $pAim->range,
                            'c_aim_id' => $aim->id,
                            'c_aim_name' => $aim->title,
                            'c_aim_range' => $aim->range,
                        ]);
                }
            }
            event(new AimDataChangeEvent($oldModel, $aim));//更新项目操作日志
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->error('修改失败');
        }
        DB::commit();
        return $this->response->item($aim, new AimDetailTransformer());
    }

    public function delete(Request $request, Aim $aim)
    {
        $aim->delete();
        return $this->response->noContent();
    }

    public function changeStatus(Request $request, Aim $aim)
    {
        if ($request->has('status')) {
            $oldModel = clone $aim;
            $aim->status = $request->get('status');
            $aim->save();
            event(new AimDataChangeEvent($oldModel, $aim));//更新项目操作日志
        }

        return $this->response->accepted();
    }

    public function relateProject(Request $request, Aim $aim)
    {
        DB::beginTransaction();
        try {
            $aim->projects()->delete();
            if ($request->has('project_ids')) {
                $projectIds = $request->get('project_ids');
                if (count($projectIds) > 0) {
                    foreach ($projectIds as $projectId) {
                        $projectId = hashid_decode($projectId);
                        $project = Project::find($projectId);
                        $aim->projects()->create([
                            'project_id' => $projectId,
                            'project_name' => $project->title,
                        ]);
                    }
                }
            } else {
                throw new \Exception('未关联项目');
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->error('关联失败');
        }
        DB::commit();

        return $this->response->created();
    }

    public function count(Request $request)
    {
        # 周期切换
        if ($request->has('period_id') && $request->get('period_id')) {
            $periodId = hashid_decode($request->get('period_id'));
            $query = Aim::where('period_id', $periodId);
        } else {
            return $this->response->errorNotFound('条件错误');
        }
        # tab部分
        $tab = $request->get('tab', 1);
        switch ($tab) {
            case 4: # 全部
                break;
            case 3: # 公司
                $query->where('range', Aim::RANGE_COMPANY);
                break;
            case 2: # 部门
                $user = Auth::guard('api')->user();
                $departmentId = DB::table('department_user')->where('user_id', $user->id)->value('department_id');
                $query->where('department_id', $departmentId)->where('range', Aim::RANGE_DEPARTMENT);
                break;
            case 1: # 个人
            default:
                $user = Auth::guard('api')->user();
                $query->where('principal_id', $user->id)->where('range', Aim::RANGE_PERSONAL);

                # 算本部门其他人完成度
                $departmentId = DB::table('department_user')->where('user_id', $user->id)->value('department_id');
                $userIds = DB::table('department_user')->where('department_id', $departmentId)->pluck('');
                $total = DB::table('aims')->where('range', Aim::RANGE_PERSONAL)->whereIn('principal_id', [])->groupBy('principal_id');
                break;
        }

        $collection = $query->get();
        $total = $collection->count();

        $timePoint = Carbon::today('PRC')->subDays(7);
        $latestCount = $collection->where('last_follow_up_at', '>', $timePoint)->count();
        $completeCount = $collection->where('status', '=', Aim::STATUS_COMPLETE)->count();
        if ($total > 0)
            $percentageAvg = number_format($collection->sum('percentage') / $total, 2);
        else
            $percentageAvg = 0;

        $data = [
            'total' => $total,
            'complete_count' => $completeCount,
            'latest_count' => $latestCount,
            'percentage_avg' => $percentageAvg,
        ];
        return  $this->response->array(['data' => $data]);
    }

    public function all (Request $request)
    {

    }
}
