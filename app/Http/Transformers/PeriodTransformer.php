<?php

namespace App\Http\Transformers;

use App\Models\Period;
use League\Fractal\TransformerAbstract;

class PeriodTransformer extends TransformerAbstract
{
    public function transform(Period $period)
    {
        return [
            'id' => hashid_encode($period->id),
            'name' => $period->name,
            'start_at' => date('Y年m月d', strtotime($period->start_at)),
            'end_at' => date('Y年m月d', strtotime($period->end_at)),
        ];
    }
}