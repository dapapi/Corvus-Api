<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FilterField extends Model
{
    protected $fillable = [
        'table_name',
        'department_id',
        'code',
        'value',
        'type',
        'operator',
        'content',
    ];

    // 定义字段类型
    const SEARCH = 1; // 文本
    const NUM = 2; // 纯数值
    const DATE = 3; // 时间
    const SELECT = 4; // 多选
    const STAR = 5; // 选艺人
    const USER = 6; // 选组织架构中的人
    const DEPARTMENT = 7; // 选组织架构中的部门
}
