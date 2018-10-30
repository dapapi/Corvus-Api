<?php

namespace App\Http\Transformers;

use App\Models\ModuleUser;
use App\User;
use League\Fractal\TransformerAbstract;

class ModuleUserTransformer extends TransformerAbstract
{
    protected $defaultIncludes = ['user'];

    public function transform(ModuleUser $moduleUser)
    {
        return [

        ];
    }

    public function includeUser(ModuleUser $moduleUser)
    {
        $user = $moduleUser->user;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

}