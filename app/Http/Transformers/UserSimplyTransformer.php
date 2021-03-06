<?php

namespace App\Http\Transformers;

use App\User;
use League\Fractal\TransformerAbstract;

class UserSimplyTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            'id' => hashid_encode($user->id),
            'name' => $user->name,
            'icon_url' => $user->icon_url,
        ];
    }
}