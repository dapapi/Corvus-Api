<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReviewQuestionItem extends Model
{

    protected $fillable = [
        'review_question_id',
        'title',
        'sort',
        'value'
    ];

    // hash
//    public function getIdAttribute() {
//        return hashid_encode($this->attributes['id']);
//    }
//
//    public function setIdAttribute($value) {
//        $this->attributes['id'] = hashid_decode($value);
//    }

}
