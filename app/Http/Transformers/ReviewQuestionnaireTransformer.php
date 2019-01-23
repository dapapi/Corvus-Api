<?php

namespace App\Http\Transformers;

use App\Models\ReviewQuestionnaire;
use League\Fractal\TransformerAbstract;

class ReviewQuestionnaireTransformer extends TransformerAbstract{

    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'producer', 'type','project', 'trails','publicity'];

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
            'reviewable_id'=> hashid_encode($reviewquestionnaire->reviewable_id),
            'reviewable_type'=> $reviewquestionnaire->reviewable_type,
            'auth_type'=> $reviewquestionnaire->auth_type,
            'created_at'=> $reviewquestionnaire->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,
            'updated_at' => $reviewquestionnaire->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒
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
    public function items() {
        return $this->hasMany(ReviewQuestionnaire::class, 'review_question_id', 'id')->orderBy('sort', 'asc');
    }

    public function review() {
        return $this->belongsTo(Review::class);
    }


}