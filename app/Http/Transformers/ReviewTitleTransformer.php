<?php

namespace App\Http\Transformers;

use App\Models\BulletinReviewTitle;
use League\Fractal\TransformerAbstract;

class ReviewTitleTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'broker','report','issues'];

    public function transform(BulletinReviewTitle $bulletinreviewtitle)
    {

        $array = [
            'id' => hashid_encode($bulletinreviewtitle->id),
            'bulletin_review_id' => $bulletinreviewtitle->bulletin_review_id,
            'creator_id' => $bulletinreviewtitle->creator_id,
            'reviewer_id' => $bulletinreviewtitle->reviewer_id,
            'title' => $bulletinreviewtitle->title,
            'status' => $bulletinreviewtitle->status,
             'issues' => $bulletinreviewtitle->issues,
            'created_at' => $bulletinreviewtitle->created_at->toDatetimeString(),
            'updated_at' => $bulletinreviewtitle->updated_at->toDatetimeString(),


        ];


        $arraySimple = [
            'id' => hashid_encode($bulletinreviewtitle->id),
            'bulletin_review_id' => $bulletinreviewtitle->bulletin_review_id,

        ];

        return $this->isAll ? $array :$arraySimple;
    }

    public function includeCreator(BulletinReviewTitle $bulletinreviewtitle)
    {

        $user = $bulletinreviewtitle->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeBroker(BulletinReviewTitle $bulletinreviewtitle)
    {

        $user = $bulletinreviewtitle->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeTasks(BulletinReviewTitle $bulletinreviewtitle)
    {

        $tasks = $bulletinreviewtitle->tasks()->createDesc()->get();
        return $this->collection($tasks, new ReportTransformer());
    }

    public function includeAffixes(BulletinReviewTitle $bulletinreviewtitle)
    {

        $affixes = $bulletinreviewtitle->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
    public function includetIssues(BulletinReviewTitle $bulletinreviewtitle)
    {
        $template = $bulletinreviewtitle->issues;
        if (!$template)
            return null;
        return $this->item($template, new ReviewTitleIssuesAnswerTransformer());
//        $tasks = $bulletinreviewtitle->issues()->createDesc()->get();
//        return $this->collection($tasks, new ReviewTitleIssuesAnswerTransformer());
    }
}