<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $table = 'approval_form_business';

    protected $fillable = [
        'form_id',
        'form_instance_number',
        'form_state',
        'business_type',
    ];
}
