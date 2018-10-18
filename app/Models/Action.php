<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'code',
        'module_id',
        'type',
        'desc'
    ];

    public function Module()
    {
        return $this->belongsTo(Module::class);
    }
}
