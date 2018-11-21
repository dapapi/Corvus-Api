<?php

namespace App\Http\Transformers;

use App\User;
use League\Fractal\TransformerAbstract;

class UsersTransformer extends TransformerAbstract
{
    private  $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }
    public function transform(User $users)
    {
        $array = [
            'id' => hashid_encode($users->id),
            'name' => $users->name,
            'phone' => $users->phone,
            'status' => $users->status,
            'entry_time' => $users->entry_time,

        ];
        return $array;
    }

}