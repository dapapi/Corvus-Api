<?php

namespace App\Http\Controllers;

use App\Http\Transformers\TaskTypeTransformer;
use App\Models\TaskType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskTypeController extends Controller
{
    public function index(Request $request)
    {
//        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $departmentId = $user->department()->first()->id;
        $taskTypes = TaskType::where('department_id', $departmentId)->get();
        return $this->response->collection($taskTypes, new TaskTypeTransformer());
    }

    public function all(Request $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $taskTypes = TaskType::paginate($pageSize);

        return $this->response->paginator($taskTypes, new TaskTypeTransformer());
    }
}
