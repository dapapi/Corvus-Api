<?php

namespace App\Http\Transformers;

use App\Models\Star;
use League\Fractal\TransformerAbstract;

class StarTransformer extends TransformerAbstract
{
    public function transform(Star $artist)
    {
        $array = [
            'id' => hashid_encode($artist->id),
            'name' => $artist->name,
        ];

        return $array;
    }
}