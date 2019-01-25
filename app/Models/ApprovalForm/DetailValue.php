<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class DetailValue extends Model
{
    protected $table = 'approval_form_detail_control_values';

    protected $fillable = [
        'form_instance_number',
        'key',
        'value',
        'sort_number',
    ];

    public function control()
    {
        return $this->belongsTo(Control::class, 'form_control_id', 'form_control_id');
    }
}
