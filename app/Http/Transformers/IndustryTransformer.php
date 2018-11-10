<?php

namespace App\Http\Transformers;

use App\Models\Industry;
use League\Fractal\TransformerAbstract;

class IndustryTransformer extends TransformerAbstract
{
    public function transform(Industry $industry)
    {
        return [
            'id' => hashid_encode($industry->id),
            'name' => $industry->name,
        ];
    }
}