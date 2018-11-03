<?php

namespace App\Http\Transformers;

use App\Models\Artist;
use League\Fractal\TransformerAbstract;

class ArtistTransformer extends TransformerAbstract
{
    public function transform(Artist $artist)
    {
        $array = [
            'id' => hashid_encode($artist->id),
            'name' => $artist->name,
        ];

        return $array;
    }
}