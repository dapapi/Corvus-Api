<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateFieldHistories extends Model
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
    const TEXT = 1; // 文本
    const RADIO = 2; // 单选
//    const STAR = 3; // 选艺人
    const TIME = 4; // 单个时间
    const TEXTAREA = 5; // 长文本
    const SELECT = 6; // 多选
    const TIME_INTERVAL = 8; // 时间区间
    const DEPARTMENT = 10; // 选组织架构中的组
    const NUM = 11; // 纯数值

    public function values()
    {
        return $this->hasMany(FieldHistorie::class, 'field_id', 'id');
    }
}
