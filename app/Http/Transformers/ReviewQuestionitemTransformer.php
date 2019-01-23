<?php

namespace App\Http\Transformers;

use App\Models\ReviewQuestionitem;
use League\Fractal\TransformerAbstract;

class ReviewQuestionitemTransformer extends TransformerAbstract{

    protected $availableIncludes = ['creator'];

    private $isAll;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(ReviewQuestionitem $reviewquestionitem)
    {
        $array = [
            'id' => hashid_encode($reviewquestionitem->id),
            'review_question_id'=> hashid_encode($reviewquestionitem->review_question_id),
           // 'creator_id'=> $reviewquestionnaire->creator_id,
            'title'=> $reviewquestionitem->title,
            'sort'=> $reviewquestionitem->sort,
            'value'=> $reviewquestionitem->value,
            'created_at'=> $reviewquestionitem->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,
            'updated_at' => $reviewquestionitem->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,
        ];
        $arraySimple = [
            'id' => hashid_encode($reviewquestionitem->id),

        ];
        return $this->isAll ? $array : $arraySimple;
    }


}