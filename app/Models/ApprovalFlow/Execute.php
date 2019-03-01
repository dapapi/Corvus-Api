<?php

namespace App\Models\ApprovalFlow;

use App\Models\DataDictionary;
use App\Models\Role;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Execute extends Model
{
    protected $table = 'approval_flow_execute';

    public $timestamps = false;

    protected $fillable = [
        'form_instance_number',
        'current_handler_id',
        'current_handler_type',
        'flow_type_id',
        'principal_level'
    ];

    public function person()
    {
        if ($this->current_handler_type == 247 or $this->current_handler_type == 246)
            return $this->belongsTo(Role::class, 'current_handler_id', 'id');
        else
            return $this->belongsTo(User::class, 'current_handler_id', 'id');
    }

    public function dictionary()
    {
        return $this->belongsTo(DataDictionary::class, 'flow_type_id', 'id');
    }
}
