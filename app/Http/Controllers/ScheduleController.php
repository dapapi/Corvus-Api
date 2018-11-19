<?php

namespace App\Http\Controllers;

use App\Http\Requests\Schedule\EditScheduleRequest;
use App\Http\Requests\Schedule\IndexScheduleReuqest;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Transformers\ScheduleTransformer;
use App\Models\Calendar;
use App\Models\Material;
use App\Models\Schedule;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    public function index(IndexScheduleReuqest $request)
    {
        $payload = $request->all();
        $thisMonth = date('n');
        $month = $request->get('month', $thisMonth);

        if ($request->has('material_ids')) {
            foreach ($payload['material_ids'] as &$id) {
                $id = hashid_decode($id);
            }
            unset($id);
        }

        if ($request->has('calendar_ids')) {
            foreach ($payload['calendar_ids'] as &$id) {
                $id = hashid_decode($id);
            }
            unset($id);
        }

        $schedules = Schedule::where(function ($query) use ($month) {
            $query->whereMonth('start_at', $month)
                ->orWhere('repeat', '!=', Schedule::NOREPEAT);
        });

        $schedules->where(function ($query) use ($request, $payload) {
            if ($request->has('material_ids'))
                $query->whereIn('material_id', $payload['material_ids']);

            if ($request->has('calendar_ids'))
                $query->orWhereIn('calendar_ids', $payload['calendar_ids']);
        });
        $schedules = $schedules->get();

        return $this->response->collection($schedules, new ScheduleTransformer());
    }

    public function store(StoreScheduleRequest $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        if ($request->has('calendar_id'))
            $payload['calendar_id'] = hashid_decode($payload['calendar_id']);

        if ($request->has('material_id'))
            $payload['material_id'] = hashid_decode($payload['material_id']);

        try {
            $schedule = Schedule::create($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('创建日程失败');
        }

        return $this->response->item($schedule, new ScheduleTransformer());
    }

    public function edit(EditScheduleRequest $request, Schedule $schedule)
    {
        $payload = $request->all();

        if ($request->has('calendar_id')) {
            $payload['calendar_id'] = hashid_decode($payload['calendar_id']);
            $calendar = Calendar::find($payload['calendar_id']);
            if (!$calendar)
                return $this->response->errorBadRequest('日历id不存在');
        }

        if ($request->has('material_id')) {
            $payload['material_id'] = hashid_decode($payload['material_id']);
            $material = Material::find($payload['material_id']);
            if (!$material)
                return $this->response->errorBadRequest('会议室id不存在');
        }

        try {
            $schedule->update($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('更新日程失败');
        }

        return $this->response->accepted();
    }

    public function detail(Request $request, Schedule $schedule)
    {
        return $this->response->item($schedule, new ScheduleTransformer());
    }
}
