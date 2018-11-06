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

    // 定义字段类型
    const TEXT = 1;
    const ENUM = 2;
    const STAR = 3;
    const TIME = 4;
    const TEXTAREA = 5;
    const GROUP_M = 6;
    const EXPECTATIONS = 7;
    const RECOMMENDATIONS = 8;
    const TIME_INTERVAL = 8;

    public function values()
    {
        return $this->hasMany(FieldValue::class, 'field_id', 'id');
    }
}
