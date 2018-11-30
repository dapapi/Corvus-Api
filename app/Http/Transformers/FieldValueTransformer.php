<?php

namespace App\Http\Transformers;

use App\Models\FieldValue;
use League\Fractal\TransformerAbstract;

class FieldValueTransformer extends TransformerAbstract
{
    protected $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(FieldValue $value)
    {
        $array = [
            'id' => hashid_encode($value->id),
            'value' => $value->value,
        ];

        if ($this->isAll)
            $array['field'] = [
                'id' => hashid_encode($value->field_id),
                'key' => $value->field->key,
                'field_type' => $value->field->field_type,
                'content' => $value->field->content,
            ];

        return $array;
    }
}