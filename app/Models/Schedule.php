<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    const OPEN = 0;
    const SECRET = 1;

    protected $fillable = [
        'title',
        'calendar_id',
        'is_allday',
        'privacy',
        'star_at',
        'end_at',
        'position',
        'repeat',
        'material_id',
        'creator_id',
        'type',
        'status',
        'desc',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
