<?php

namespace App\Http\Transformers;

use App\Models\TemplateField;
use League\Fractal\TransformerAbstract;

class TemplateFieldTransformer extends TransformerAbstract
{
    public function transform(TemplateField $field)
    {
        $array = [
            'id' => hashid_encode($field->id),
            'key' => $field->key,
            'field_type' => $field->field_type,
        ];
        if ($field->field_type == TemplateField::RADIO || $field->field_type == TemplateField::SELECT) {
            $array['content'] = explode('|', $field->content);
        }

        return $array;
    }
}