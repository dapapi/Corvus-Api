<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class Control extends Model
{
    protected $table = 'approval_form_controls';

    protected $fillable = [
        'form_control_id',
        'form_id',
        'control_id',
        'pid',
        'sort_number',
        'required',
        'created_by',
        'created_at',
        'order_by',
    ];
}
