<?php

namespace App\Http\Transformers;

use App\Models\PrivacyUser;

use League\Fractal\TransformerAbstract;

class PrivacyUserTransformer extends TransformerAbstract
{
    protected $availableIncludes = [];


    public function transform(PrivacyUser $privacyUser)
    {

            $array = [

                'project_bill_id' => hashid_encode($privacyUser->id),
                'user_id' => $privacyUser->user_id,
                'field' => $privacyUser->moduleable_field,



            ];


        return $array;
    }

}
