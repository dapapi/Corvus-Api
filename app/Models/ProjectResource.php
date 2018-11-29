<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectResource extends Model
{

    protected $fillable = [
        'project_id',
        'resourceable_id',
        'resourceable_type',
        'resource_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function resourceable()
    {
        return $this->morphTo();
    }

    public function resource()
    {
        return $this->belongsTo(Module::class);
    }
}
