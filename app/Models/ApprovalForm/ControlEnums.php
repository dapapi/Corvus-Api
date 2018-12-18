<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class ControlEnums extends Model
{
    protected $table = 'approval_form_control_enums';

    protected $fillable = [
        'form_control_id',
        'enum_value',
        'sort_number'
    ];
}
