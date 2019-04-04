<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;

class ProjectImplodeDetailTransformer extends TransformerAbstract
{
    public function transform($item)
    {
        return [
            'id' => hashid_encode($item->id),
            'form_instance_number' => $item->form_instance_number,
            'title' => $item->project_name,
            'type' => $item->project_type,
            'priority' => $item->priority,
            'status' => $item->status,
            'itemed_expenditure' => "" . $item->itemed_expenditure,
            'start_at' => $item->start_at,
            'end_at' => $item->end_at,
            'created_at' => $item->created_at->toDateTimeString(),
            'updated_at' => $item->updated_at->toDateTimeString(),
            'desc' => $item->desc,
            // 日志内容
            'last_follow_up_at' => $item->last_follow_up_at,
            'last_updated_user' => $item->last_updated_user,
            'last_updated_at' => $item->last_updated_at,
            'power' => $item->power,
            'powers' => $item->powers
        ];

    }
}