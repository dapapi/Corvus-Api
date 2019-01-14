<?php

namespace App\Models\ApprovalForm;

use App\Interfaces\ApprovalInstanceInterface;
use App\Models\DataDictionary;
use Illuminate\Database\Eloquent\Model;

class Instance extends Model implements approvalinstanceinterface
{
    protected $table = 'approval_form_instances';

    protected $fillable = [
        'form_instance_id',
        'form_id',
        'form_instance_mumble',
        'apply_id',
        'form_type',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'order_by',
    ];

    public function form()
    {
        return $this->belongsTo(ApprovalForm::class, 'form_id', 'id');
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
