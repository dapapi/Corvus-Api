<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\PersonalJob;


class JobTransformer extends TransformerAbstract
{
    public function transform(PersonalJob $job)
    {
        return [
            'id' => hashid_encode($job->id),
            'user_id' => $job->id,
            'rank' => $job->rank,
            'eport' => $job->eport,
            'positive_time' => $job->positive_time,
            'management' => $job->management,
            'siling' => $job->siling,
            'first_work_time' => $job->first_work_time,
            'modulation_siling' => $job->modulation_siling,
            'work_ling' => $job->work_ling,
            'modulation_work_ling' => $job->modulation_work_ling,

            'subordinate_sum' => $job->subordinate_sum,
            'work_city' => $job->work_city,
            'taxcity' => $job->taxcity,

            'contract_start_time' => $job->contract_start_time,
            'contract_end_time' => $job->contract_end_time,
            'recruitment_ditch' => $job->recruitment_ditch,
            'recruitment_type' => $job->recruitment_type,
            'other_ditch' => $job->other_ditch,
            'user_id' => $job->user_id,
            'entry_time' => $job->entry_time,
        ];


    }
}