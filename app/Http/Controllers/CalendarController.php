<?php

namespace App\Http\Controllers;

use App\Http\Requests\Calendar\EditCalendarRequest;
use App\Http\Requests\Calendar\StoreCalendarRequest;
use App\Http\Transformers\CalendarTransformer;
use App\Models\Calendar;
use App\ModuleableType;
use DemeterChain\C;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function all(Request $request)
    {
        // todo 按权限筛选
        $calendars = Calendar::get();

        return $this->response->collection($calendars, new CalendarTransformer());
    }

    public function store(StoreCalendarRequest $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        if ($request->has('star')) {
            $payload['starable_id'] = hashid_decode($payload['star']);


            //todo 暂时为硬编码
            if ($user->company->name != '泰洋川禾') {
                $payload['starable_type'] = ModuleableType::BLOGGER;
            } else {
                $payload['starable_type'] = ModuleableType::STAR;
            }
        }


        $payload['creator_id'] = $user->id;

        DB::beginTransaction();
        //todo 加参与人
        try {
            $calendar = Calendar::create($payload);
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->item($calendar, new CalendarTransformer());
    }

    public function edit(EditCalendarRequest $request, Calendar $calendar)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        if ($request->has('star')) {
            $payload['starable_id'] = hashid_decode($payload['star']);

            //todo 暂时为硬编码
            if ($user->company->name != '泰洋川禾') {
                $payload['starable_type'] = ModuleableType::BLOGGER;
            } else {
                $payload['starable_type'] = ModuleableType::STAR;
            }
        }

        try {
            $calendar->update($payload);
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改日历失败');
        }

        return $this->response->accepted();
    }

    public function detail(Request $request, Calendar $calendar)
    {
        return $this->response->item($calendar, new CalendarTransformer());
    }

    public function delete(Request $request, Calendar $calendar)
    {
        $calendar->status = Calendar::STATUS_FROZEN;
        $calendar->save();
        $calendar->delete();
        return $this->response->noContent();
    }

    public function recover(Request $request, Calendar $calendar)
    {
        $calendar->restore();
        $calendar->status = Calendar::STATUS_NORMAL;
        $calendar->save();

        return $this->response->item($calendar, new CalendarTransformer());
    }

    public function forceDelete(Request $request, Calendar $calendar)
    {
        $calendar->forceDelete();
        return $this->response->noContent();
    }
}