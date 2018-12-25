<?php

namespace App\Http\Transformers;

use App\Models\ProjectReturnedMoney;
use League\Fractal\TransformerAbstract;

class ProjectReturnedMoneyTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['money','type'];


    public function transform(ProjectReturnedMoney $projectReturnedMoney)
    {

            $array = [

                'id' => hashid_encode($projectReturnedMoney->id),
                'contract_id' => hashid_encode($projectReturnedMoney->contract_id),
                'project_id' => hashid_encode($projectReturnedMoney->project_id),
//                'creator_id' => hashid_encode($projectReturnedMoney->creator_id),
                'principal_id' => hashid_encode($projectReturnedMoney->principal_id),
                'issue_name' => $projectReturnedMoney->issue_name,
                'plan_returned_money' => $projectReturnedMoney->plan_returned_money,
                'plan_returned_time' =>  date('Y-m-d',strtotime($projectReturnedMoney->plan_returned_time)),
          //      'project_returned_money_type_id' => $projectReturnedMoney->project_returned_money_type_id,
                'desc' => $projectReturnedMoney->desc,
                'created_at'=> $projectReturnedMoney->created_at->toDateTimeString(),
                'updated_at' => $projectReturnedMoney->updated_at->toDateTimeString()
            ];


        return $array;
    }
    public function includeMoney(ProjectReturnedMoney $projectReturnedMoney)
    {
        $project = $projectReturnedMoney->money()->createDesc()->get();
        return $this->collection($project, new ProjectReturnedMoneyShowTransformer());
    }

}
