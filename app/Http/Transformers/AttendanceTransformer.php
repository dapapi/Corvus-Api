<?php

namespace App\Http\Transformers;

use App\Models\Attendance;
use Illuminate\Validation\Rule;
use League\Fractal\TransformerAbstract;

class AttendanceTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'broker'];

    public function transform(Attendance $attendance)
    {
        return [
            'type'  =>  $attendance->type,
            'start_at'  =>  $attendance->start_at,
            'end_at'    =>  $attendance->end_at,
            'number'    =>  $attendance->number,
            'cause' =>  $attendance->cause,
            'affixes'   =>  $attendance->affixes,
            'approval_flow' =>  $attendance->approval_flow,
            'notification_person'   =>  $attendance->notification_person,
            'leave_type'    =>  $attendance->leave_type,
            'place' =>  $attendance->place,
            'status'    => $attendance->status
        ];
    }
}