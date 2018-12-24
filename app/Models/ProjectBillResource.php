<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProjectBillResource extends Model
{

    protected $fillable = [
        'expenses',
        'papi_divide',
        'bigger_divide',
        'my_divide',
        'resourceable_id',
        'resourceable_title',
        'resourceable_type',
        'creator_id'
    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }


}
