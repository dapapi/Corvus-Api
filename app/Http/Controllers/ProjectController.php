<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\EditProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\ProjectRequest;
use App\Http\Transformers\ProjectTransformer;
use App\Models\FieldValue;
use App\Models\ModuleUser;
use App\Models\Project;
use App\Models\Star;
use App\Models\TemplateField;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    // 项目列表
    public function index(Request $request)
    {
        $payload = $request->all();

        if ($request->has('page_size')) {
            $pageSize = $payload['page_size'];
        } else {
            $pageSize = config('page_size');
        }

        $projects = Project::paginate($pageSize);

        return $this->response->paginator($projects, new ProjectTransformer());
    }


    // todo 按个人角度筛选 待测试
    public function my(Request $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        $userId = hashid_decode($user->id);

        if ($request->has('page_size')) {
            $pageSize = $payload['page_size'];
        } else {
            $pageSize = config('api.page_size');
        }

        if ($request->has('type')) {
            $type = $payload['type'];
        } else {
            $type = 1;
        }

        if ($type == 2) {
            $projects = Project::where('principal_id', $userId)->paginate($pageSize);
        } elseif ($type == 3) {
            $projects = ModuleUser::where('user_id', $userId)
                ->where('moduleable_type', ModuleableType::PROJECT)->paginate($pageSize);
        } else {
            $projects = Project::where('creator_id', $userId)->paginate($pageSize);
        }

        return $this->response->paginator($projects, new ProjectTransformer());
    }

    public function store(StoreProjectRequest $request)
    {
        // todo 可能涉及筛选可选线索
        $payload = $request->all();

        if ($payload['type'] != 5 && !$request->has('fields')) {
            return $this->response->errorBadRequest('缺少参数');
        } elseif ($request->has('fields')) {
            foreach ($payload['fields'] as $key => $val) {
                $fieldId = hashid_decode((int)$key);
                $field = TemplateField::where('module_type', $payload['type'])->find($fieldId);
                if (!$field) {
                    return $this->response->errorBadRequest('字段与项目类型匹配错误');
                }
            }
            unset($key);
            $payload['trail_id'] = hashid_decode($payload['trail']['id']);
        }

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        $payload['principal_id'] = hashid_decode($payload['principal_id']);

        DB::beginTransaction();
        try {
            $project = Project::create($payload);
            $projectId = $project->id;

            if ($payload['type'] != 5) {
                foreach ($payload['fields'] as $key => $val) {
                    FieldValue::create([
                        'field_id' => hashid_decode((int)$key),
                        'project_id' => $projectId,
                        'value' => $val,
                    ]);
                }

                // todo 优化，这部分操作应该有对应仓库
                // todo 操作日志的时候在对应的trail也要记录
                $trail = Trail::find($payload['trail_id']);
                foreach ($payload['trail'] as $key => $val) {
                    if ($key == 'id')
                        continue;

                    if ($key == 'lock') {
                        $trail->lock_status = $val;
                        continue;
                    }

                    if ($key == 'fee') {
                        $trail->fee = 100 * $val;
                        continue;
                    }

                    if ($key == 'expectations') {
                        TrailStar::where('trail_id', $trail->id)->delete();
                        foreach ($payload['expectations'] as $expectation) {
                            $starId = hashid_decode($expectation);

                            if (Star::find($starId))
                                TrailStar::create([
                                    'trail_id' => $trail->id,
                                    'star_id' => $starId,
                                    'type' => TrailStar::EXPECTATION,
                                ]);
                        }
                        continue;
                    }

                    if ($key == 'recommendations') {
                        TrailStar::where('trail_id', $trail->id)->delete();
                        foreach ($payload['recommendations'] as $recommendation) {
                            $starId = hashid_decode($recommendation);
                            if (Star::find($starId))
                                TrailStar::create([
                                    'trail_id' => $trail->id,
                                    'star_id' => $starId,
                                    'type' => TrailStar::RECOMMENDATION,
                                ]);
                        }
                        continue;
                    }
                    $trail[$key] = $val;
                }
                $trail->save;
            }

        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

        return $this->response->item($project, new ProjectTransformer());

    }

    public function edit(EditProjectRequest $request, Project $project)
    {
        $payload = $request->all();

        if ($request->has('principal_id'))
            $payload['principal_id'] = hashid_decode($payload['principal_id']);

        DB::beginTransaction();
        try {
            foreach ($payload as $key => $value) {
                $project[$key] = $value;
            }
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }

        return $this->response->accepted();
    }

    public function detail(Request $request, Project $project)
    {
        return $this->response->item($project, new ProjectTransformer());
    }


    public function delete(Request $request, Project $project)
    {

    }

}
