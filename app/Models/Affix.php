<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Affix extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'affixable_id',
        'affixable_type',
        'title',
        'url',
        'type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function affixable()
    {
        return $this->morphTo();
    }

}
