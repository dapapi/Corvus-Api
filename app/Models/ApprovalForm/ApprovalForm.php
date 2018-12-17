<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class ApprovalForm extends Model
{
    protected $table = 'approval_forms';

    protected $fillable = [
        'form_id',
        'name',
        'group_id',
        'modified',
        'description',
        'icon',
        'change_type',
        'sort_number',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'order_by',
    ];
}
