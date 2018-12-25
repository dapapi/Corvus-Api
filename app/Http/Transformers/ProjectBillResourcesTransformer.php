<?php

namespace App\Http\Transformers;

use App\Models\ProjectBillsResource;

use League\Fractal\TransformerAbstract;

class ProjectBillResourcesTransformer extends TransformerAbstract
{



    public function transform(ProjectBillsResource $projectBillsResource)
    {

            $array = [

                'project_bill_id' => hashid_encode($projectBillsResource->project_bill_id),
                'expenses' => $projectBillsResource->expenses,
                'papi_divide' => $projectBillsResource->papi_divide,
                'bigger_divide' => $projectBillsResource->bigger_divide,
                'my_divide' => $projectBillsResource->my_divide,




            ];


        return $array;
    }

}
