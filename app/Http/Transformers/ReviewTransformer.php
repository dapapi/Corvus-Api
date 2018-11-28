<?php

namespace App\Http\Transformers;

use App\Models\BulletinReview as Review;
use League\Fractal\TransformerAbstract;

class ReviewTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'broker','report'];

    public function transform(Review $review)
    {

        $array = [
            'id' => hashid_encode($review->id),
            'template_id' => $review->template_id,
            'template' => $review->template,
            'member' => $review->member,
            'title' => $review->title,
            'status' => $review->status,


        ];


//        $arraySimple = [
//            'id' => hashid_encode($review->id),
//            'name' => $review->name,
//            'avatar' => $review->avatar
//        ];

        return $this->isAll ? $array :'';
    }

    public function includeCreator(Review $review)
    {

        $user = $review->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeTemplate(Review $review)
    {

        $template = $review->template;
        if (!$template)
            return null;
        return $this->item($template, new ReviewTransformer());
    }

    public function includeBroker(Review $review)
    {

        $user = $review->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeReport(Review $review)
    {
        $template = $review->template;
        if (!$template)
            return null;
        return $this->item($template, new ReviewTransformer());
    }
    public function includeTasks(Review $review)
    {

        $tasks = $review->tasks()->createDesc()->get();
        return $this->collection($tasks, new ReviewTransformer());
    }

    public function includeAffixes(Review $review)
    {

        $affixes = $review->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
}