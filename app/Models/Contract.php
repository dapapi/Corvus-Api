<?php

namespace App\Models;

use App\Traits\OperateLogTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    protected $fillable = [
        'contract_number',
        'title',
        'form_instance_number',
        'contract_start_date',
        'contract_end_date',
        'contract_money',
        'contract_sharing_ratio',
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
    use OperateLogTrait;

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
