<?php

namespace App\Http\Transformers;

use App\Models\BulletinReview;
use League\Fractal\TransformerAbstract;

class ReviewTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['template','type'];
    private $isAll;
    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }


    public function transform(BulletinReview $bulletinreview)
    {

        $array = [
            'id' => hashid_encode($bulletinreview->id),
            'template_id' => $bulletinreview->template_id,
            'template' => $bulletinreview->template,
            'member' => $bulletinreview->member,
            'title' => $bulletinreview->title,
            'status' => $bulletinreview->status,
        ];


//        $arraySimple = [
//            'id' => hashid_encode($review->id),
//            'name' => $review->name,
//            'avatar' => $review->avatar
//        ];

        return $this->isAll ? $array :'';
    }

    public function includeCreator(BulletinReview $bulletinreview)
    {
        $user = $bulletinreview->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeBroker(BulletinReview $bulletinreview)
    {

        $user = $bulletinreview->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeTmplate(BulletinReview $bulletinreview)
    {
        $template = $bulletinreview->template;
        if (!$template)
            return null;
        return $this->item($template, new ReportTransformer());
    }
    public function includeTasks(BulletinReview $bulletinreview)
    {

        $tasks = $bulletinreview->tasks()->createDesc()->get();
        return $this->collection($tasks, new ReviewTransformer());
    }
    public function includeAffixes(BulletinReview $bulletinreview)
    {

        $affixes = $bulletinreview->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
}