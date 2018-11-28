<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    const TYPE_MEETING_ROOM = 1;
    const TYPE_STUDIO = 2;

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
