<?php

namespace App\Http\Transformers;

use App\Models\GroupRoles;
use League\Fractal\TransformerAbstract;

class GroupRolesTransformer extends TransformerAbstract
{
    public function transform(GroupRoles $groupRole)
    {
        return [
            'id' => hashid_encode($groupRole->id),
            'name' => $groupRole->name,

        ];
    }
}