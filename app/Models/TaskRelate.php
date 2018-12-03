<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskRelate extends Model
{
    protected $fillable = [
        'project_id',
        'moduleable_id',
        'moduleable_type',
        'type',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
