<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ReviewUser extends Model
{
    protected $fillable = [
        'reviewquestionnaire_id',
        'user_id',
    ];



    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
