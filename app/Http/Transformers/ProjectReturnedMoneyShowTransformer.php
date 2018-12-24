<?php

namespace App\Http\Transformers;

use App\Models\ProjectReturnedMoney;
use League\Fractal\TransformerAbstract;

class ProjectReturnedMoneyShowTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['type'];
    public function transform(ProjectReturnedMoney $projectReturnedMoney)
    {

            $array = [

                'id' => hashid_encode($projectReturnedMoney->id),

                'plan_returned_money' => $projectReturnedMoney->plan_returned_money,
                'plan_returned_time' => date('Y-m-d',strtotime($projectReturnedMoney->plan_returned_time)),
             //   'project_returned_money_type_id' => $projectReturnedMoney->project_returned_money_type_id,


            ];


        return $array;
    }
    public function includeType(ProjectReturnedMoney $projectReturnedMoney)
    {

        $type = $projectReturnedMoney->type;
        if (!$type)
            return null;
        return $this->item($type, new ProjectReturnedMoneyTypeTransformer());
    }
}
