<?php

namespace App\Http\Transformers;

use App\Models\BulletinReview;
use League\Fractal\TransformerAbstract;

class ReviewTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['template','member','creator'];
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
            'template_type' =>  $bulletinreview->template->frequency,
            'member' => $bulletinreview->memberName->name,
            'title' => $bulletinreview->title,
            'titles' =>  explode(';',substr($bulletinreview->titles,0,strlen($bulletinreview->titles)-1)),
            'countstatus' => $bulletinreview->countstatus,
            'created_time' => $bulletinreview->created_time->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,
            'status' => $bulletinreview->status,
            'created_at' => $bulletinreview->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,
            'updated_at' => $bulletinreview->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,

        ];


        $arraySimple = [
            'id' => hashid_encode($bulletinreview->id),
            'template_id' => hashid_encode($bulletinreview->template_id),
            'template' => $bulletinreview->template->template_name,
            'member' => $bulletinreview->memberName->name,
            'title' => $bulletinreview->title,
            'status' => $bulletinreview->status,
            'created_at' => $bulletinreview->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,
            'updated_at' => $bulletinreview->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,
        ];

        return $this->isAll ? $array :$arraySimple;
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

}