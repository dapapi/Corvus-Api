<?php

namespace App\Models\ApprovalFlow;

use App\Models\DataDictionary;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    protected $table = 'approval_flow_change';
    public $timestamps = false;


    protected $fillable = [
        'form_instance_number',
        'change_id',
        'change_at',
        'change_state',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'change_id', 'id');
    }

    public function dictionary()
    {
        return $this->belongsTo(DataDictionary::class, 'change_state', 'id');
    }
}
