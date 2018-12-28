<?php

namespace App\Http\Transformers;

use App\Models\TemplateField;
use App\Models\TemplateFieldHistories;
use League\Fractal\TransformerAbstract;

class TemplateFieldHistoriesTransformer extends TransformerAbstract
{
    protected $projectId = null;
    public function __construct($projectId = null)
    {
        $this->projectId = $projectId;
    }

    protected $defaultIncludes= ['values'];

    public function transform(TemplateFieldHistories $field)
    {
        $array = [
            'id' => hashid_encode($field->id),
            'key' => $field->key,
            'field_type' => $field->field_type,
        ];
        if ($field->field_type == TemplateFieldHistories::RADIO || $field->field_type == TemplateFieldHistories::SELECT) {
            $array['content'] = explode('|', $field->content);
        }

        return $array;
    }

    public function includeValues(TemplateFieldHistories $field)
    {
        $value = $field->values()->where('project_id', $this->projectId)->first();
        if (!$value)
            return null;

        return $this->item($value, new FieldValueHistoriesTransformer(false));
    }
}