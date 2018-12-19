<?php

namespace App\Http\Transformers;

use App\Models\ReviewQuestionnaire;
use League\Fractal\TransformerAbstract;

class ReviewQuestionnaireShowTransformer extends TransformerAbstract{

    protected $availableIncludes = ['creator', 'sum'];

  //  protected $defaultIncludes = ['creator', 'sum'];
    private $isAll;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(ReviewQuestionnaire $reviewquestionnaire)
    {
        $array = [
            'id' => hashid_encode($reviewquestionnaire->id),
            'name'=> $reviewquestionnaire->name,
            // 'creator_id'=> $reviewquestionnaire->creator_id,
            'deadline'=> $reviewquestionnaire->deadline,
            'reviewable_id'=> hashid_encode($reviewquestionnaire->reviewable_id),
            'reviewable_type'=> $reviewquestionnaire->reviewable_type,
            'auth_type'=> $reviewquestionnaire->auth_type,
            'created_at'=> $reviewquestionnaire->created_at->toDateTimeString(),
            'updated_at' => $reviewquestionnaire->updated_at->toDateTimeString()
        ];
        $arraySimple = [
            'id' => hashid_encode($reviewquestionnaire->id),

        ];
        return $this->isAll ? $array : $arraySimple;
    }

    public function includeCreator(ReviewQuestionnaire $reviewquestionnaire)
    {
        $user = $reviewquestionnaire->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeSum(ReviewQuestionnaire $reviewquestionnaire)
    {
        $reviewanswer = $reviewquestionnaire->sum;
        return $this->collection($reviewanswer, new ReviewAnswerSumTransformer());

    }
    public function includeitems(ReviewQuestionnaire $reviewquestionnaire) {
      // dd($reviewquestionnaire->items);
    }




}