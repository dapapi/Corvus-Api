<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleDataView extends Model
{
    protected $table = 'role_data_view';

    protected $fillable = [
        'role_id',
        'resource_id',
        'data_view_id',
        'data_view_sql',
    ];
    //数据权限sql
    //普遍搜索条件规则
    const DATA_VIEW_SQL = "{\"rules\": [{\"field\" : \"creator_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}, {\"field\" : \"principal_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}], \"op\" : \"or\"}";
    //艺人规则
    const STAR_DATA_VIEW_SQL = "{\"rules\": [{\"field\" : \"creator_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}], \"op\" : \"or\"}";
    //博主规则
    const BLOGGER_DATA_VIEW_SQL = "{\"rules\": [{\"field\" : \"creator_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}], \"op\" : \"or\"}";
    //任务规则
    const TASK_DATA_VIEW_SQL = "{\"rules\": [{\"field\" : \"tasks.creator_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}, {\"field\" : \"tasks.principal_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}], \"op\" : \"or\"}";
}
