<?php

namespace App\Models\ApprovalFlow;

use App\Interfaces\ChainInterface;
use App\Models\Role;
use App\User;
use Illuminate\Database\Eloquent\Model;

class ChainFixed extends Model implements ChainInterface
{
    protected $table = 'approval_flow_chain_fixed';

    protected $fillable = [
        'form_id',
        'pre_id',
        'next_id',
        'condition_id',
        'approver_type',
        'principal_level'
    ];

    public function next()
    {
        if  ($this->approver_type == 246 or $this->approver_type == 247)
            return $this->hasOne(Role::class, 'id', 'next_id');
        else
            return $this->hasOne(User::class, 'id', 'next_id');
    }
}
