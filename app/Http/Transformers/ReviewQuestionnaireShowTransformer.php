<?php

namespace App\Http\Transformers;

use App\Models\ReviewQuestionnaire;
use League\Fractal\TransformerAbstract;

class ReviewQuestionnaireShowTransformer extends TransformerAbstract{

    protected $availableIncludes = ['creator', 'sum','production','reviewanswer','reviewanswer.creator'];

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
            'excellent'=> $reviewquestionnaire->excellent,
            'excellent_sum'=> $reviewquestionnaire->excellent_sum,
           // 'reviewable_id'=> hashid_encode($reviewquestionnaire->reviewable_id),
          //  'reviewable_type'=> $reviewquestionnaire->reviewable_type,
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
    public function includeProduction(ReviewQuestionnaire $reviewquestionnaire) {

        $reviewanswer = $reviewquestionnaire->production->where('id',$reviewquestionnaire->reviewable_id)->get();
        return $this->collection($reviewanswer, new ProductionTransformer());
    }
    public function includeReviewanswer(ReviewQuestionnaire $reviewquestionnaire) {

        $reviewanswer = $reviewquestionnaire->reviewanswer()->get();
        return $this->collection($reviewanswer, new ReviewUserTransformer());

    }



}