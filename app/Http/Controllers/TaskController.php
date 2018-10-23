<?php

namespace App\Http\Controllers;

use App\Models\Task;

class TaskController extends Controller
{
    public function index()
    {
        dd(Task::where('id', 1)->first()->affixes);
    }
}
