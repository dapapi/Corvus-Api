<?php

namespace App\Http\Transformers;

use App\Models\AnnouncementScope;
use League\Fractal\TransformerAbstract;

class AnnouncementScopeTransformer extends TransformerAbstract
{

    public function transform(AnnouncementScope $announcementScope)
    {

        $array = [
            'id' => hashid_encode($announcementScope->id),
            'announcement_id' => hashid_encode($announcementScope->announcement_id),  //公告id
            'department_id' => hashid_encode($announcementScope->department_id), // 部门id
            'created_at' => $announcementScope->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,
            'updated_at' => $announcementScope->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,


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