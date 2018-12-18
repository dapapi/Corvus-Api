<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class ControlProperty extends Model
{
    protected $table = 'approval_form_control_properties';

    protected $fillable = [
        'form_control_id',
        'property_id',
        'property_value',
    ];
}
