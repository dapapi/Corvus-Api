<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class Instance extends Model
{
    protected $table = 'approval_form_instances';

    protected $fillable = [
        'form_instance_id',
        'form_id',
        'form_instance_mumble',
        'apply_id',
        'form_type',
        'create_by',
        'create_at',
        'updated_by',
        'updated_at',
        'order_by',
    ];

    public function form()
    {
        return $this->hasOne(ApprovalForm::class, 'form_id', 'id');
    }
}
