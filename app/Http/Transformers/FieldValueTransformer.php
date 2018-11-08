<?php

namespace App\Http\Transformers;

use App\Models\FieldValue;
use League\Fractal\TransformerAbstract;

class FieldValueTransformer extends TransformerAbstract
{
    public function transform(FieldValue $value)
    {
        $array = [
            'id' => hashid_encode($value->id),
            'field' => [
                'id' => hashid_encode($value->field_id),
                'key' => $value->field->key,
                'type' => $value->field->type,
                'content' => $value->field->content,
            ],
            'value' => $value->value,
        ];

        return $array;
    }
}