<?php

namespace App\Models\ApprovalFlow;

use Illuminate\Database\Eloquent\Model;

class Execute extends Model
{
    protected $table = 'approval_flow_execute';

    public $timestamps = false;

    protected $fillable = [
        'form_instance_number',
        'current_handler_id',
        'flow_type_id'
    ];


}
