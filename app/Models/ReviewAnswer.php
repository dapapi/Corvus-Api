<?php

namespace App\Models;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewAnswer extends Model {
    use SoftDeletes;

    protected $fillable = [
        'review_question_id',
        'review_id',
        'review_question_item_id',
        'content',
        'user_id'
    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
