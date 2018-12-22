<?php

namespace App\Http\Transformers;

use App\Models\ProjectBill;
use App\Models\Project;
use League\Fractal\TransformerAbstract;

class ProjectBillTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['expendituresum'];


    public function transform(ProjectBill $projectbill)
    {

            $array = [
                'project_bill_id' => hashid_encode($projectbill->project_bill_id),
                'account_year' => $projectbill->account_year,
                'account_period' => $projectbill->account_period,
                'voucher_number' => $projectbill->voucher_number,
                'project_kd_code' => $projectbill->project_kd_code,
                'project_kd_name' => $projectbill->project_kd_name,
                'bill_number' => $projectbill->bill_number,
                'expense_name' => $projectbill->expense_name,
                'artist_name' => $projectbill->artist_name,
                'money' => $projectbill->money,
                'action_user' => $projectbill->action_user,
                'expense_type' => $projectbill->expense_type,
                'apply_reason' => $projectbill->apply_reason,
                'pay_rec_time' => $projectbill->pay_rec_time,
                //'expendituresum'=> $projectbill->expendituresum


            ];


        return $array;
    }

}
