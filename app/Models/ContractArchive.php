<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractArchive extends Model
{
    protected $fillable = [
        'contract_id',
        'contract_number',
        'form_instance_number',
        'archive',
        'file_name',
        'size',
    ];

    public function contract()
    {
        $this->belongsTo(Contract::class, 'contract_id', 'id');
    }
}
