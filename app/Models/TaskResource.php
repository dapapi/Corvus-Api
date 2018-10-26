<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskResource extends Model
{

    protected $fillable = [
        'task_id',
        'resourceable_id',
        'resourceable_type',
        'resource_id',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function resourceable()
    {
        return $this->morphTo();
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
