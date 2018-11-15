<?php

namespace App\Http\Controllers;

use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));
    }

    public function store(StoreScheduleRequest $request)
    {

    }

    public function edit()
    {

    }

    public function detail()
    {

    }
}
