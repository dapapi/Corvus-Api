<?php

namespace App\Http\Controllers;

use App\Http\Transformers\TaskTransformer;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = config('app.page_size');
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        }

        dd(Task::where('id', 1)->first()->affixes);
    }

    public function show(Task $task)
    {
        $task = Task::where('id', $task->id)->first();
        return $this->response()->item($task, new TaskTransformer());
    }

    public function store(Request $request)
    {

    }
}
