<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'creator_id',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }
}
