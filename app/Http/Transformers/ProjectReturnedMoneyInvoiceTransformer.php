<?php

namespace App\Http\Transformers;

use App\Models\ProjectReturnedMoney;
use League\Fractal\TransformerAbstract;

class ProjectReturnedMoneyInvoiceTransformer extends TransformerAbstract
{



    public function transform(ProjectReturnedMoney $projectReturnedMoney)
    {

            $array = [

                        'invoiceSum' => $projectReturnedMoney->practicalsums,
            ];


        return $array;
    }

}
