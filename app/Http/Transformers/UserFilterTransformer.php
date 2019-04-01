<?php

namespace App\Http\Transformers;

use App\User;
use League\Fractal\TransformerAbstract;


class UserFilterTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        $array = [
            'id' => hashid_encode($user->id),
            'name' => $user->name,
        ];
        return $array;
    }



}