<?php

namespace App\Models\ApprovalFlow;

use Illuminate\Database\Eloquent\Model;

class ChainFixed extends Model
{
    protected $table = 'approval_flow_chain_fixed';

    protected $fillable = [
        'form_id',
        'pre_id',
        'next_id',
        'condition_id'
    ];
}
