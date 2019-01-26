<?php

namespace App\Models;
use App\User;
use App\Models\ReviewAnswer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewQuestion extends Model {
    use SoftDeletes;

    protected $fillable = [
        'review_id',
        'title',
        'type',
        'sort'
    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
    public function items() {

        return $this->hasMany(ReviewQuestionItem::class, 'review_question_id', 'id')->orderBy('value', 'desc');
    }

    public function selectrows() {


        return $this->hasMany(ReviewAnswer::class, 'review_question_id', 'id');
    }
    public function reviewQuestionnaires() {
        return $this->belongsTo(ReviewQuestionnaire::class);
    }
    public function bulletinReview()
    {
        return $this->hasMany(ReviewAnswer::class,'template_id','id' );
    }
//    public function getStatusAttribute()
//    {
//        //
//        $user = Auth::guard('api')->user();
//        $query = $this->BulletinReview()->where('member', $user->id);
//
//        switch ($this->frequency) {
//            case 1:
//                break;
//            case 2:
//                break;
//            case 3:
//                break;
//            case 4:
//                break;
//            default:
//                $query->where('created_at', '>=', Carbon::today()->toDateTimeString())->where('created_at', '<=',Carbon::tomorrow()->toDateTimeString());
//                break;
//        }
//
//        $review = $query->first();
//
//        if ($review){
//            $re['id'] = hashid_encode($review->id);
//            $re['status'] = $review->status;
//
//            return  $re;
//        }else{
//            return null;
//        }
//    }
//
//    public function score() {
//        if ($this->review()->first()->reviewable_type == ReviewableType::VIDEO) {
//            $answers = ReviewAnswer::where('review_question_id', $this->id)->get();
//        } else {
//            // todo 推广问卷的中间分
//            return null;
//        }
//        $score = 0;
//        if (is_null($answers)) {
//            return $score;
//        } else {
//            switch ($this->type) {
//                case ReviewQuestionType::RADIO:
////                    foreach ($answers as $answer) {
////                        $score += $this->items()->where('id', $answer->content)->value('value');
////                    }
////                    return $score;
////                    break;
//                // todo 多选，星级题的得分
////                    foreach ($answers as $answer) {
////                        $score += $this->items()->where('id', $answer->content)->value('value');
////                    }
////                    return $score;
////                    break;
//                case ReviewQuestionType::CHECKBOX:
//                case ReviewQuestionType::RATING:
//                    foreach ($answers as $answer) {
//                        $score += $this->items()->where('id', $answer->content)->value('value');
//                    }
//                    return $score;
//                    break;
//                case ReviewQuestionType::TEXT:
//                default:
//                    return 0;
//                    break;
//            }
//        }
//
//    }
//    public function oneScore(User $user) {
//        $answers = ReviewAnswer::where('review_question_id', $this->id)->where('user_id', $user->id)->get();
//        $score = 0;
//        if (is_null($answers)) {
//            return $score;
//        } else {
//            switch ($this->type) {
//                case ReviewQuestionType::RADIO:
//                case ReviewQuestionType::CHECKBOX:
//                case ReviewQuestionType::RATING:
//                    foreach ($answers as $answer) {
//                        $score += $this->items()->where('id', $answer->content)->value('value');
//                    }
//                    return $score;
//                    break;
//                case ReviewQuestionType::TEXT:
//                default:
//                    return 0;
//                    break;
//            }
//        }
//
//    }
}
