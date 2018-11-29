<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Record;


class RecordTransformer extends TransformerAbstract
{
    public function transform(Record $record)
    {
        return [
            'id' => hashid_encode($record->id),
            'user_id' => $record->user_id,
            'unit_name' => $record->unit_name,
            'department' => $record->department,
            'position' => $record->position,
            'entry_time' => $record->entry_time,
            'departure_time' => $record->departure_time,
            'monthly_pay' => $record->monthly_pay,
            'departure_why' => $record->departure_why,


        ];


    }
}