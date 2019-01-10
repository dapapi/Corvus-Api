<?php

namespace App\Models\ApprovalForm;

use App\Interfaces\ApprovalParticipantInterFace;
use App\Models\DataDictionary;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model implements ApprovalParticipantInterFace
{
    protected $table = 'approval_form_participants';

    public $timestamps = false;

    protected $fillable = [
        'form_instance_number',
        'notice_id',
        'notice_type',
        'created_by',
        'created_at',
    ];

    public function dictionary()
    {
        return $this->belongsTo(DataDictionary::class, 'notice_type', 'id');
    }

    public function notice()
    {
        return $this->belongsTo(User::class, 'notice_id', 'id');
    }
}
