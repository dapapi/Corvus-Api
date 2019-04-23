<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTalent extends Model
{
    protected $table = 'project_talent';

    protected $fillable = [
        'project_id',
        'talent_id',
        'talent_type',
        'talent_name',
    ];

    public function talent()
    {
        return $this->morphTo('talent', 'talent_type', 'talent_id');
    }
}
