<?php

namespace App\Models\ApprovalForm;

use App\Interfaces\ApprovalInstanceInterface;
use App\Models\DataDictionary;
use Illuminate\Database\Eloquent\Model;

class Business extends Model implements ApprovalInstanceInterface
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

    public function status()
    {
        return $this->belongsTo(DataDictionary::class, 'form_status', 'id');
    }

    public function fields()
    {
        return $this->hasMany(InstanceValue::class, 'form_instance_number', 'form_instance_number');
    }
}
