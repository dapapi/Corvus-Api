<?php

namespace App\Http\Transformers;

use App\Models\Calendar;
use App\ModuleableType;
use League\Fractal\TransformerAbstract;

class CalendarIndexTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['starable','principal','participants','schdule'];

    protected $defaultIncludes = ['principal'];
    public function transform(Calendar $calendar)
    {
        return [
            'id' => hashid_encode($calendar->id),
            'title' => $calendar->title,
            'color' => $calendar->color,
            'principal_id' => empty($calendar->principal_id)? null:hashid_encode($calendar->principal_id),   //
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
                return $this->item($star, new BloggerFilterTransformer(false));
                break;
            case ModuleableType::STAR:
                return $this->item($star, new StarFilterTransformer(false));
                break;
            default:
                return null;
        }
    }

    public function includeParticipants(Calendar $calendar)
    {
        $participants = $calendar->participants;
        return $this->collection($participants, new UserFilterTransformer());
    }
    public function includePrincipal(Calendar $calendar)
    {
        $principal = $calendar->principal;
        if(!$principal)
            return null;
        return $this->item($principal, new UsersTransformer());
    }
    public function includeSchdule(Calendar $calendar)
    {
        $schdules = $calendar->schedules()->get();
        return $this->collection($schdules,new ScheduleTransformer());
    }

}