<?php

namespace App\Models\ApprovalFlow;

use App\Interfaces\ChainInterface;
use App\User;
use Illuminate\Database\Eloquent\Model;

class ChainFree extends Model implements ChainInterface
{
    protected $table = 'approval_flow_chain_free';
    public $timestamps = false;

    protected $fillable = [
        'form_number',
        'pre_id',
        'next_id',
        'sort_number'
    ];

    public function next()
    {
        return $this->hasOne(User::class, 'id', 'next_id');
    }
}
