<?php

namespace App\Models\ApprovalFlow;

use App\Interfaces\ApprovalParticipantInterFace;
use App\Models\ApprovalForm\ApprovalForm;
use App\Models\DataDictionary;
use App\User;
use Illuminate\Database\Eloquent\Model;

class FixedParticipant extends Model implements ApprovalParticipantInterFace
{
    protected $table = 'approval_form_fixed_participants';

    protected $fillable = [
        'form_id',
        'notice_id',
        'notice_type',
    ];

    public function form()
    {
        return $this->belongsTo(ApprovalForm::class, 'form_id', 'form_id');
    }

    public function notice()
    {
        return $this->belongsTo(User::class, 'notice_id', 'id');
    }

    public function dictionary()
    {
        return $this->belongsTo(DataDictionary::class, 'notice_type', 'id');
    }
}
