<?php

namespace App\Models\ApprovalFlow;

use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    protected $table = 'approval_flow_change';

    protected $fillable = [
        'form_instance_number',
        'change_id',
        'change_at',
        'change_state',
        'comment',
    ];
}
