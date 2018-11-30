<?php

namespace App\Http\Transformers;

use App\Models\TemplateField;
use League\Fractal\TransformerAbstract;

class TemplateFieldTransformer extends TransformerAbstract
{
    protected $projectId = null;
    public function __construct($projectId = null)
    {
        $this->projectId = $projectId;
    }

    protected $defaultIncludes= ['values'];

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

    public function includeValues(TemplateField $field)
    {
        $value = $field->values()->where('project_id', $this->projectId)->first();
        if (!$value)
            return null;

        return $this->item($value, new FieldValueTransformer(false));
    }
}