<?php

namespace App\Models\ApprovalFlow;

use App\Interfaces\ChainInterface;
use App\User;
use Illuminate\Database\Eloquent\Model;

class ChainFree extends Model implements ChainInterface
{
    protected $table = 'approval_flow_chain_free';

    protected $fillable = [
        'form_number',
        'pre_id',
        'next_id'
    ];

    public function next()
    {
        return $this->hasOne(User::class, 'id', 'next_id');
    }
}
