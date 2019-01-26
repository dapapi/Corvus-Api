<?php

namespace App\Http\Transformers;

use App\Models\ReviewQuestion;
use Illuminate\Support\Facades\DB;
use App\Models\DepartmentPrincipal;
use App\Models\ReviewAnswer;
use Illuminate\Support\Facades\Auth;
use League\Fractal\TransformerAbstract;

class ReviewQuestionTransformer extends TransformerAbstract
{


    protected $availableIncludes = ['creator', 'items','selectrows','reviewquestion'];
  //  protected $defaultIncludes = ['reviewquestion'];
    private $isAll;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(ReviewQuestion $reviewquestion)
    {
        $array = [
            'id' => hashid_encode($reviewquestion->id),
          //  'review_id'=> hashid_encode($reviewquestion->review_id),
            'title'=> $reviewquestion->title,
            'type'=> $reviewquestion->type,
            'sort'=> $reviewquestion->sort,
            'created_at'=> $reviewquestion->created_at->toDateTimeString(),
            'updated_at' => $reviewquestion->updated_at->toDateTimeString()
        ];
        $arraySimple = [
            'id' => hashid_encode($reviewquestion->id),

        ];
        return $this->isAll ? $array : $arraySimple;
    }

    public function includeItems(ReviewQuestion $reviewquestion)
    {
        $tasks = $reviewquestion->items;
        return $this->collection($tasks, new ReviewQuestionitemTransformer());
    }
    public function includeSelectrows(ReviewQuestion $reviewquestion)
    {


        $selectrows = $reviewquestion->selectrows()->get();
        $user = Auth::guard('api')->user();
        $arr  = ReviewAnswer::where('review_id', $reviewquestion->review_id)->where('user_id',$user->id)->groupby('user_id')->get();
        $array[] = ['user_id',$user->id];
        $arrdate = !empty(DepartmentPrincipal::where($array)->first());
        if($arrdate) {
            //return $this->item($selectrows, new BloggerProducerTransformer());
            return $this->collection($selectrows, new ReviewAnswerSelectrowsTransformer());
        }else if(count($arr)>0){
            $data = false;
            return $this->collection($selectrows, new ReviewAnswerSelectrowsTransformer($data));

        }else{
            return $this->null();
        }
    }
//    public function review() {
//        return $this->belongsTo(ReviewQuestionnaire::class);
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
