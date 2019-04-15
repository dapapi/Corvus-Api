<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aim extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    protected $table = 'aims';

    protected $fillable = [
        'title',
        'range',
        'department_id',
        'department_name',
        'period_id',
        'period_name',
        'type',
        'amount_type',
        'amount',
        'position',
        'talent_level',
        'aim_level',
        'principal_id',
        'principal_name',
        'creator_id',
        'creator_name',
        'percentage',
        'deadline',
        'desc',
    ];

    public function parents()
    {
        $this->belongsTo(AimParent::class, 'p_aim_id', 'id');
    }

    public function childs()
    {
        $this->hasMany(AimParent::class, 'c_aim_id', 'id');
    }
}
