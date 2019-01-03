<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    protected $fillable = [
        'contract_number',
        'form_instance_number',
        'creator_id',
        'creator_name',
        'project_id',
        'client_id',
        'type',
        'stars',
        'star_type',
        'updater_id',
        'updater_name',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
