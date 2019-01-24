<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class ProjectBillsResourceUser extends Model
{
    protected $table = 'project_bills_resources_users';
    protected $fillable = [
        'money',
        'moduleable_id',
        'moduleable_title',
        'moduleable_type',
        'type',

    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }


}
