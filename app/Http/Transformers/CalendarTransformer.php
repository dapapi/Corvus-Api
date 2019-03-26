<?php

namespace App\Http\Transformers;

use App\Models\Calendar;
use App\ModuleableType;
use League\Fractal\TransformerAbstract;

class CalendarTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['starable', 'participants','schdule'];

    public function transform(Calendar $calendar)
    {
        return [
            'id' => hashid_encode($calendar->id),
            'title' => $calendar->title,
            'color' => $calendar->color,
            'principal_id' => $calendar->principal_id,   //
            'starable_type' => $calendar->starable_type,   
            'privacy' => $calendar->privacy,
        ];
    }

    public function includeStarable(Calendar $calendar)
    {
        $star = $calendar->starable;
        if (!$star)
            return null;

        switch ($calendar->starable_type) {
            case ModuleableType::BLOGGER:
                return $this->item($star, new BloggerTransformer(false));
                break;
            case ModuleableType::STAR:
                return $this->item($star, new StarTransformer(false));
                break;
            default:
                return null;
        }
    }

    public function includeParticipants(Calendar $calendar)
    {
        $participants = $calendar->participants;
        return $this->collection($participants, new UserTransformer());
    }
    public function includeSchdule(Calendar $calendar)
    {
        $schdules = $calendar->schedules()->get();
        return $this->collection($schdules,new ScheduleTransformer());
    }

}