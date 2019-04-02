<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectImplodeTalent extends Model
{
    protected $table = 'project_implode_talents';

    protected $fillable = [
        'project_id',
        'team_m',
        'team_producer',
        'talent_id',
        'producer',
        'producer_id',
        'broker',
        'broker_id',
        'talent_type',
    ];

    public function projectImplode()
    {
        return $this->belongsTo(ProjectImplode::class, 'project_id', 'project_id');
    }
}
