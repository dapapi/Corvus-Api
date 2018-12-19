<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataDictionary extends Model
{
    use SoftDeletes;

    protected $table = 'data_dictionaries';

    protected $fillable = [
        'parent_id',
        'code',
        'val',
        'name',
        'description',
        'icon',
        'sort_number',
        'created_by',
        'updated_by',
        'order_by',
    ];
}
