<?php

namespace App\Http\Transformers;

use App\Models\ProjectReturnedMoneyType;
use League\Fractal\TransformerAbstract;

class ProjectReturnedMoneyTypeTransformer extends TransformerAbstract
{


    public function transform(ProjectReturnedMoneyType $projectReturnedMoneyType)
    {

            $array = [

                'id' => hashid_encode($projectReturnedMoneyType->id),
                'type' => $projectReturnedMoneyType->type,
                'plan_returned_money' => $projectReturnedMoneyType->name,



            ];


        return $array;
    }

}
