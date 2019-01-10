<?php

namespace App\Http\Transformers;

use App\Models\PrivacyUser;

use League\Fractal\TransformerAbstract;

class PrivacyUserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user','creator'];

    private $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;

    }

    public function transform(PrivacyUser $privacyuser)
    {

        if($this->isAll) {

            $array = [

                'project_bill_id' => hashid_encode($privacyuser->id),
                'user_id' => $privacyuser->user_id,
                'field' => $privacyuser->moduleable_field
            ];
        }else{

            $array = [

                'field' => $privacyuser->moduleable_field,

              //  'user_ids' => $privacyuser->user_ids

            ];
        }

        return $array;
    }
    public function includeUser(PrivacyUser $privacyuser)
    {

        $project = $privacyuser->user;
        if (!$project)
            return null;

        return $this->item($project, new UserTransformer());
    }
    public function includeCreator(PrivacyUser $privacyuser)
    {

        $project = $privacyuser->creator;
        if (!$project)
            return null;

        return $this->item($project, new UserTransformer());
    }
}
