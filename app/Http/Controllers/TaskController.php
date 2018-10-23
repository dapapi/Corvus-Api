<?php

namespace App\Http\Controllers;

use App\Http\Transformers\TaskTransformer;
use App\Models\Task;

class TaskController extends Controller
{
    public function index()
    {
        dd(Task::where('id', 1)->first()->affixes);
    }

    public function show(Task $task)
    {
        $task = Task::where('id', $task->id)->first();
        return $this->response()->item($task, new TaskTransformer());
    }
}
