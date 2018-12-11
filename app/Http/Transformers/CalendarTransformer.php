<?php

namespace App\Http\Transformers;

use App\Models\Calendar;
use App\ModuleableType;
use League\Fractal\TransformerAbstract;

class CalendarTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['starable', 'participants'];

    public function transform(Calendar $calendar)
    {
        return [
            'id' => hashid_encode($calendar->id),
            'title' => $calendar->title,
            'color' => $calendar->color,
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

}