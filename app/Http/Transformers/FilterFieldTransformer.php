<?php

namespace App\Http\Transformers;

use App\Models\FilterField;
use League\Fractal\TransformerAbstract;

class FilterFieldTransformer extends TransformerAbstract
{
    public function transform(FilterField $field)
    {
        $array = [
            'id' => hashid_encode($field->id),
            'code' => $field->code,
            'value' => $field->value,
            'type' => $field->type,
            'operator' => json_decode($field->operator),
            'content' => json_decode($field->content),
        ];

        return $array;
    }
}