<?php

namespace App\Http\Transformers;

use App\Models\Groups;
use League\Fractal\TransformerAbstract;

class GroupsTransformer extends TransformerAbstract
{
    public function transform(Groups $groups)
    {
        return [
            'id' => hashid_encode($groups->id),
            'name' => $groups->name,

        ];
    }
}