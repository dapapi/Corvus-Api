<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Position;


class PositionTransformer extends TransformerAbstract
{
    public function transform(Position $position)
    {
        return [
            'id' => hashid_encode($position->id),
            'name' => $position->name,
        ];


    }
}