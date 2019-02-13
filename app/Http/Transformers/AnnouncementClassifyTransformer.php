<?php

namespace App\Http\Transformers;

use App\Models\AnnouncementClassify;
use App\Models\AnnouncementScope;
use League\Fractal\TransformerAbstract;

class AnnouncementClassifyTransformer extends TransformerAbstract
{

    public function transform(AnnouncementClassify $announcementClassify)
    {

        $array = [
            'id' => hashid_encode($announcementClassify->id),
            'name' => $announcementClassify->name,  //公告id
            'sum' => $announcementClassify->sum['sum'],
            'created_at' => $announcementClassify->created_at->toDateTimeString(),//时间去掉秒,,
            'updated_at' => $announcementClassify->updated_at->toDateTimeString(),//时间去掉秒,


        ];



        return $array;
    }

    public function includeCreator(AnnouncementScope $announcementScope)
    {

        $user = $announcementScope->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }



}