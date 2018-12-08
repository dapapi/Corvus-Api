<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    protected $fillable = [
        'name',
        'phone',
        'position',
        'client_id',
        'type',
        'status',
    ];

    const STATUS_NORMAL = 1;
    const STATUS_FROZEN = 2;

    const TYPE_NORMAL = 1;
    const TYPE_KEY = 2;

    protected $dates = ['deleted_at'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
