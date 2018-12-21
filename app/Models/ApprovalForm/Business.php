<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $table = 'approval_form_business';

    public $timestamps = false;

    protected $fillable = [
        'form_id',
        'form_instance_number',
        'form_status',
        'business_type',
    ];

    public function form()
    {
        return $this->belongsTo(ApprovalForm::class, 'form_id', 'form_id');
    }

}
