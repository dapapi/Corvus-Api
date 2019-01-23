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
            'bulletin_review_title_id' => $bulletinreviewtitleissuesanswer->bulletin_review_title_id,
            'issues' => $bulletinreviewtitleissuesanswer->issues,
            'type' => $bulletinreviewtitleissuesanswer->type,
            'answer' => $bulletinreviewtitleissuesanswer->answer,
            'created_at' => $bulletinreviewtitleissuesanswer->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,
            'updated_at' => $bulletinreviewtitleissuesanswer->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,


        ];


        return $array;
    }



}