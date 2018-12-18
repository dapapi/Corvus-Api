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

}
