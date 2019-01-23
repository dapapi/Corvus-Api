<?php

namespace App\Http\Transformers;

use App\Models\ProjectReturnedMoney;
use League\Fractal\TransformerAbstract;

class ProjectReturnedMoneyPracticalTransformer extends TransformerAbstract
{



    public function transform(ProjectReturnedMoney $projectReturnedMoney)
    {

            $array = [

                'practicalsum' =>$projectReturnedMoney->practicalsums,

            ];


        return $array;
    }

}
