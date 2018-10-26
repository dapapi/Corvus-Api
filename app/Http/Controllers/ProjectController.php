<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Transformers\ProjectTransformer;
use App\Models\ModuleUser;
use App\Models\Project;
use App\ModuleableType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function store(ProjectRequest $request)
    {
        // todo 基础字段 模板字段分别存不同表
        $payload = $request->all();


    }

    public function edit(Request $request)
    {

    }

    public function delete(Request $request)
    {

    }
}
