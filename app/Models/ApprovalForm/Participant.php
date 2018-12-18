<?php

namespace App\Models\ApprovalForm;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $table = 'approval_form_participants';

    protected $fillable = [
        'form_instance_number',
        'notice_id',
        'created_by',
        'created_at',
    ];
}
