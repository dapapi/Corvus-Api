<?php

namespace App\Http\Transformers;

use App\Models\Schedule;
use function GuzzleHttp\Psr7\str;
use League\Fractal\TransformerAbstract;

class ScheduleTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['calendar', 'material', 'creator', 'participants', 'affixes','task','project'];

    public function transform(Schedule $schedule)
    {
        $array = [
            'id' => hashid_encode($schedule->id),
            'title' => $schedule->title,
            'is_allday' => $schedule->is_allday,
            'privacy' => $schedule->privacy,
            'start_at' => date('Y-m-d H:i',strtotime($schedule->start_at)),
            'end_at' => date('Y-m-d H:i',strtotime($schedule->end_at)),
            'position' => $schedule->position,
            'repeat' => $schedule->repeat,
            'desc' => $schedule->desc,
            'icon_url'  =>  $schedule->icon_url,
            'remind'    =>  $schedule->remind,
            'calendar_id'   =>  $schedule->calendar_id,
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

    public function includeAffixes(Schedule $schedule)
    {
        $affixes = $schedule->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
    public function includeParticipants(Schedule $schedule)
    {
        $participants = $schedule->participants;

        return $this->collection($participants, new UserTransformer());
    }
    public function includeTask(Schedule $schedule){

        $task = $schedule->schedulerelate->where('moduleable_type','task');
        return $this->collection($task, new ScheduleRelateTaskTransformer());
    }
    public function includeProject(Schedule $schedule){
        $project = $schedule->schedulerelate->where('moduleable_type','project');

        return $this->collection($project, new ScheduleRelateProjectTransformer());
    }
}