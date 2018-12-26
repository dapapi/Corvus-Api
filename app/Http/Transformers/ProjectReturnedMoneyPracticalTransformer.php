<?php

namespace App\Http\Transformers;

use App\Models\ProjectReturnedMoney;
use League\Fractal\TransformerAbstract;

class ProjectReturnedMoneyPracticalTransformer extends TransformerAbstract
{



    public function transform(ProjectReturnedMoney $projectReturnedMoney)
    {

            $array = [

                'practicalsum' =>$projectReturnedMoney->practicalsum[0]['practicalsums'],

            ];


        return $array;
    }

}
