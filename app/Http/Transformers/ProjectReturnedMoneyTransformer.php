<?php

namespace App\Http\Transformers;

use App\Models\ProjectReturnedMoney;
use League\Fractal\TransformerAbstract;

class ProjectReturnedMoneyTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['money','type','practicalsum','invoicesum'];


    public function transform(ProjectReturnedMoney $projectReturnedMoney)
    {

            $array = [

                'id' => hashid_encode($projectReturnedMoney->id),
                'contract_id' => hashid_encode($projectReturnedMoney->contract_id),
                'project_id' => hashid_encode($projectReturnedMoney->project_id),
//                'creator_id' => hashid_encode($projectReturnedMoney->creator_id),
                'principal_id' => hashid_encode($projectReturnedMoney->principal_id),
                'issue_name' => '第'.$projectReturnedMoney->issue_name.'期',
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
    public function includePracticalSum(ProjectReturnedMoney $projectReturnedMoney)
    {
        $reviewanswer = $projectReturnedMoney->practicalsum->first();
        if(!$reviewanswer){
            return null;
        }
        return $this->item($reviewanswer, new ProjectReturnedMoneyPracticalTransformer());

    }

    public function includeInvoiceSum(ProjectReturnedMoney $projectReturnedMoney)
    {

        $reviewanswer = $projectReturnedMoney->invoiceSum->first();
        if(!$reviewanswer){
            return null;
        }
        return $this->item($reviewanswer, new ProjectReturnedMoneyInvoiceTransformer());

    }
}
