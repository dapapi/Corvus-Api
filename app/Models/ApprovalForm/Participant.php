<?php

namespace App\Models\ApprovalForm;

use App\Models\DataDictionary;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
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
}
