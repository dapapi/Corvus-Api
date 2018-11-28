<?php

namespace App\Http\Transformers;

use App\Models\BulletinReviewTitleIssuesAnswer;
use League\Fractal\TransformerAbstract;

class ReviewTitleIssuesAnswerTransformer extends TransformerAbstract
{


    public function transform(BulletinReviewTitleIssuesAnswer $bulletinreviewtitleissuesanswer)
    {

        $array = [
            'id' => hashid_encode($bulletinreviewtitleissuesanswer->id),
            'bulletin_review_id' => $bulletinreviewtitleissuesanswer->bulletin_review_id,
            'issues' => $bulletinreviewtitleissuesanswer->issues,
            'answer' => $bulletinreviewtitleissuesanswer->reviewer_id,
            'title' => $bulletinreviewtitleissuesanswer->answer,
            'created_at' => $bulletinreviewtitleissuesanswer->created_at->toDatetimeString(),
            'updated_at' => $bulletinreviewtitleissuesanswer->updated_at->toDatetimeString(),


        ];


        return $array;
    }



}