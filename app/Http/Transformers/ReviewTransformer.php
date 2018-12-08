<?php

namespace App\Http\Transformers;

use App\Models\BulletinReview;
use League\Fractal\TransformerAbstract;

class ReviewTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['template','member'];
    private $isAll;
    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }


    public function transform(BulletinReview $bulletinreview)
    {
        $array = [
            'id' => hashid_encode($bulletinreview->id),
            'template_id' => hashid_encode($bulletinreview->template_id),
            'template' => $bulletinreview->template->template_name,
            'member' => $bulletinreview->memberName->name,
            'title' => $bulletinreview->title,
            'countstatus' => $bulletinreview->countstatus,
            'status' => $bulletinreview->status,
            'created_at' => $bulletinreview->created_at->toDateTimeString(),
        ];


        $arraySimple = [
            'id' => hashid_encode($bulletinreview->id),
            'template_id' => hashid_encode($bulletinreview->template_id),
            'template' => $bulletinreview->template->template_name,
            'member' => $bulletinreview->memberName->name,
            'title' => $bulletinreview->title,
            'status' => $bulletinreview->status,
            'created_at' => $bulletinreview->created_at->toDateTimeString(),
        ];

        return $this->isAll ? $array :$arraySimple;
    }


    public function includeBroker(BulletinReview $bulletinreview)
    {

        $user = $bulletinreview->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

}