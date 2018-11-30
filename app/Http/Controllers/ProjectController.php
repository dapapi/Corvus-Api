<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\EditProjectRequest;
use App\Http\Requests\Project\SearchProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Transformers\ProjectTransformer;
use App\Models\Client;
use App\Models\FieldValue;
use App\Models\ModuleUser;
use App\Models\Project;
use App\Models\Star;
use App\Models\TemplateField;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\Repositories\ProjectRepository;
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

        $pageSize = $request->get('page_size', config('app.page_size'));

        $projects = Project::paginate($pageSize);

        return $this->response->paginator($projects, new ProjectTransformer());
    }

    public function all(Request $request)
    {
        $isAll = $request->get('all', false);

        $projects = Project::orderBy('created_at', 'desc')->get();
        return $this->response->collection($projects, new ProjectTransformer($isAll));
    }

    public function myAll(Request $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        if ($request->has('page_size')) {
            $pageSize = $payload['page_size'];
        } else {
            $pageSize = config('api.page_size');
        }

        $type = $request->get('type', 0);

        $status = $request->get('status', 0);

        $projects = DB::table('projects')->select('projects.*');
        switch ($status) {
            case Project::STATUS_NORMAL:
                $projects->where('status', Project::STATUS_NORMAL);
                break;
            case Project::STATUS_COMPLATE:
                $projects->where('status', Project::STATUS_COMPLATE);
                break;
            case Project::STATUS_FROZEN:
                $projects->where('status', Project::STATUS_FROZEN);
                break;
            default:
                break;
        }

        $projects->where(function($query) use ($userId) {
            $query->where('creator_id', $userId)->orWhere('principal_id', $userId);
        });

        $query = DB::table('projects')->select('projects.*')->join('module_users', function ($join) use ($userId) {
            $join->on('module_users.moduleable_id', '=', 'projects.id')
                ->where('module_users.moduleable_type', ModuleableType::PROJECT)
                ->where('module_users.user_id', $userId);
        });

        switch ($status) {
            case Project::STATUS_NORMAL:
                $query->where('status', Project::STATUS_NORMAL);
                break;
            case Project::STATUS_COMPLATE:
                $query->where('status', Project::STATUS_COMPLATE);
                break;
            case Project::STATUS_FROZEN:
                $query->where('status', Project::STATUS_FROZEN);
                break;
            default:
                break;
        }

        $query->union($projects);

        $querySql = $query->toSql();
        $result = Project::rightJoin(DB::raw("($querySql) as a"), function ($join) {
            $join->on('projects.id', '=', 'a.id');
        })->mergeBindings($query)
            ->orderBy('a.created_at', 'desc')
            ->paginate($pageSize);

        return $this->response->paginator($result, new ProjectTransformer());
    }

    public function my(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', 0);
        $type = $request->get('type', 1);

        $query = Project::select('projects.*');

        switch ($type) {
            case 2://我负责
                $query->where('principal_id', $user->id);
                break;
            case 3://我参与
                $query = $user->participantProjects();
                break;
            case 1://我创建
            default:
                $query->where('creator_id', $user->id);
                break;
        }
        switch ($status) {
            case Project::STATUS_NORMAL://进行中
                $query->where('status', Project::STATUS_NORMAL);
                break;
            case Project::STATUS_COMPLETE://完成
                $query->where('status', Project::STATUS_COMPLETE);
                break;
            case Project::STATUS_FROZEN://终止
                $query->where('status', Project::STATUS_FROZEN);
                break;
            default:
                break;
        }
        $projects = $query->orderBy('created_at', 'desc')->paginate($pageSize);

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
                $trail->save();
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

        if ($request->has('fields')) {
            foreach ($payload['fields'] as $key => $val) {
                $fieldId = hashid_decode((int)$key);
                $field = TemplateField::where('module_type', $payload['type'])->find($fieldId);
                if (!$field) {
                    return $this->response->errorBadRequest('字段与项目类型匹配错误');
                }
            }
        }

        DB::beginTransaction();
        try {
            $project->update($payload);
            $projectId = $project->id;

            if ($request->has('fields')) {
                foreach ($payload['fields'] as $key => $val) {
                    $fieldId = hashid_decode((int)$key);
                    $fieldValue = FieldValue::where('field_id', $fieldId)->where('project_id', $projectId)->first();
                    if ($fieldValue) {
                        $fieldValue->value = $val;
                        $fieldValue->save();
                    } else {
                        FieldValue::create([
                            'field_id' => $fieldId,
                            'project_id' => $projectId,
                            'value' => $val,
                        ]);
                    }
                }

            }

            if ($request->has('trail')) {
                if (array_key_exists('id',$payload['trail']))
                    $payload['trail']['id'] = hashid_decode($payload['trail']['id']);

                foreach ($payload['trail'] as $key => $val) {
                    if ($key == 'id') {
                        $trail = Trail::find($val);
                        if (!$trail)
                            throw new Exception('线索不存在或已删除');

                        $project->trail_id = $trail->id;
                    } else {
                        break;
                    }

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
                    $trail->save();
                }
            }

        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('修改失败,'. $exception->getMessage());
        }

        return $this->response->accepted();
    }

    public function detail(Request $request, Project $project)
    {
        return $this->response->item($project, new ProjectTransformer());
    }


    public function delete(Request $request, Project $project)
    {
        try {
            $project->status = Project::STATUS_DEL;
            $project->save();
            $project->delete();
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('删除失败');
        }

        return $this->response->noContent();
    }

    public function recover(Request $request, Project $project)
    {
        $project->restore();
        $project->status = Project::STATUS_NORMAL;
        $project->save();

        return $this->response->item($project, new ProjectTransformer());
    }

    public function changeStatus(Request $request, Project $project)
    {
        if (!$request->has('status'))
            return $this->response->errorBadRequest('参数错误');

        $status = $request->get('status');
        switch ($status) {
            case Project::STATUS_COMPLETE:
                $project->complete_at = now();
                $project->status = $status;
                break;
            case Project::STATUS_FROZEN:
                $project->stop_at = now();
                $project->status = $status;
                break;
            default:
                break;
        }

        $project->save();

        return $this->response->item($project, new ProjectTransformer());
    }

    public function search(SearchProjectRequest $request)
    {
        $type = $request->get('type');
        $id = hashid_decode($request->get('id'));

        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        } else {
            $pageSize = config('app.page_size');
        }

        switch ($type) {
            case 'clients':
                $projects = Project::select('projects.*')->join('trails', function($join) {
                    $join->on('projects.trail_id', '=', 'trails.id');
                })->where('trails.client_id', '=', $id)
                    ->paginate($pageSize);
                break;
            default:
                return $this->response->noContent();
                break;
        }

        return $this->response->paginator($projects, new ProjectTransformer());
    }

    /**
     * 获取明星下的项目
     * @param Request $request
     * @return mixed
     */
    public function getStarProject(Request $request)
    {
        $star_id = $request->get('star_id',null);
        $star_id = hashid_decode($star_id);
        $result = ProjectRepository::getProjectBySatrId($star_id);
        //todo 这里的返回值status没有返回数字，返回的是中文所以用不了transfromer
//        return $this->response->collection($result, new ProjectTransformer());
        return $result;
    }
}
