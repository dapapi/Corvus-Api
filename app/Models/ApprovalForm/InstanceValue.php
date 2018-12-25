<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class InstanceValue extends Model
{
    protected $table = 'approval_form_instance_values';

    protected $fillable = [
        'form_instance_number',
        'form_control_id',
        'form_control_value'
    ];
}
