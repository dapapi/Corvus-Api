<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'approval_form_groups';

    protected $fillable = [
        'name',
        'sort_number',
        'description',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'order_by',
    ];
}
