<?php

namespace App\Http\Transformers;

use App\Models\Material;
use League\Fractal\TransformerAbstract;

class MaterialTransformer extends TransformerAbstract
{
    public function transform(Material $material)
    {
        return [
            'id' => hashid_encode($material->id),
            'name' => $material->name,
            'type' => $material->type,
        ];
    }
}