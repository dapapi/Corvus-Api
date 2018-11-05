<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateField extends Model
{
    protected $table = 'project_template_fields';

    protected $fillable = [
        'key',
        'field_type',
        'content',
        'module_type',
        'status',
        'is_secret',
    ];

    const TYPE_TEXT = 1;
    const TYPE_ENUM = 2;
    const TYPE_STAR = 3;
    const TYPE_DATE = 4;
    const TYPE_TEXTAREA = 5;
    const TYPE_GROUP_M = 6;
    const EXPECTATIONS = 7;
    const RECOMMENDATIONS = 8;

    public function values()
    {
        return $this->hasMany(FieldValue::class, 'field_id', 'id');
    }
}
