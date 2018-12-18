<?php

namespace App\Models\ApprovalFlow;

use Illuminate\Database\Eloquent\Model;

class ChainFree extends Model
{
    protected $table = 'approval_flow_chain_free';

    protected $fillable = [
        'form_number',
        'pre_id',
        'next_id'
    ];
}
