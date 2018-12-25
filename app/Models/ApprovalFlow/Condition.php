<?php

namespace App\Models\ApprovalFlow;

use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    protected $table = 'approval_flow_condition';

    protected $fillable = [
        'flow_condition_id',
        'form_id',
        'form_control_id',
        'condition'
    ];
}
