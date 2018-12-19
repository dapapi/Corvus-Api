<?php

namespace App\Http\Transformers;

use App\Models\ReviewAnswer;
use League\Fractal\TransformerAbstract;

class ReviewAnswerSelectrowsTransformer extends TransformerAbstract{

    protected $availableIncludes = ['creator'];

    private $isAll;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(ReviewAnswer $reviewanswer)
    {
        $array = [
            'review_question_item_id'=> hashid_encode($reviewanswer->review_question_item_id),
            'user_id' => $reviewanswer->user_id

        ];
        $arraySimple = [
            'id' => hashid_encode($reviewanswer->id),

        ];
        return $this->isAll ? $array : $arraySimple;
    }
    public function includeCreator(ReviewAnswer $reviewanswer)
    {
        $user = $reviewanswer->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

}