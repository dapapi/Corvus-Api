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
    const DATA_VIEW_SQL = "{\"rules\": [{\"field\" : \"creator_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}, {\"field\" : \"principal_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}], \"op\" : \"or\"}";
    const STAR_DATA_VIEW_SQL = "{\"rules\": [{\"field\" : \"creator_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}], \"op\" : \"or\"}";
    const BLOGGER_DATA_VIEW_SQL = "{\"rules\": [{\"field\" : \"creator_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}], \"op\" : \"or\"}";
}
