<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\PersonalSalary;

class SalaryTransformer extends TransformerAbstract
{
    public function transform(PersonalSalary $salary)
    {
        return [
            'id' => hashid_encode($salary->id),
            'user_id' => $salary->id,
            'entry_time' => $salary->entry_time,
            'trial_end_time' => $salary->trial_end_time,
            'pdeparture_time' => $salary->pdeparture_time,
            'share_department' => $salary->share_department,
            'jobs' => $salary->jobs,
            'income_tax' => $salary->income_tax,
            'personnel_category' => $salary->personnel_category,

        ];
    }
}