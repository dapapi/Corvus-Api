<?php

namespace App\Http\Transformers;

use App\Models\Schedule;
use League\Fractal\TransformerAbstract;

class ScheduleTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['calendar', 'material', 'creator'];

    public function transform(Schedule $schedule)
    {
        $array = [
            'id' => hashid_encode($schedule->id),
            'title' => $schedule->title,
            'is_allday' => $schedule->is_allday,
            'privacy' => $schedule->privacy,
            'start_at' => $schedule->start_at,
            'end_at' => $schedule->end_at,
            'position' => $schedule->position,
            'repeat' => $schedule->repeat,
            'desc' => $schedule->desc,
        ];

        return $array;
    }

    public function includeCalendar(Schedule $schedule)
    {
        $calendar = $schedule->calendar;
        if (!$calendar)
            return null;

        return $this->item($calendar, new CalendarTransformer());
    }

    public function includeMaterial(Schedule $schedule)
    {
        $material = $schedule->material;
        if (!$material)
            return null;

        return $this->item($material, new MaterialTransformer());
    }


    public function includeCreator(Schedule $schedule)
    {
        $creator = $schedule->creator;
        if (!$creator)
            return null;

        return $this->item($creator, new UserTransformer());
    }
}