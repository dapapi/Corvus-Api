<?php

namespace App\Http\Transformers;

use App\Models\ReviewUser;
use League\Fractal\TransformerAbstract;

class ReviewUserTransformer extends TransformerAbstract{

    protected $availableIncludes = ['creator','users'];

    private $isAll;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(ReviewUser $reviewuser)
    {

        $array = [
             'id' => hashid_encode($reviewuser->id),
            'user_id' =>  hashid_encode($reviewuser->user_id),

        ];
        $arraySimple = [
            'id' => hashid_encode($reviewuser->id),

        ];
        return $this->isAll ? $array : $arraySimple;
    }
    public function includeCreator(ReviewUser $reviewuser)
    {

        $user = $reviewuser->creator;
       if (!$user)
           return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeUsers(ReviewUser $reviewuser)
    {

        $user = $reviewuser->users;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
}