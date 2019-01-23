<?php

namespace App\Http\Transformers;

use App\Models\ProjectStatusLogs;

use League\Fractal\TransformerAbstract;


class ProjectCourseTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['users'];



    public function transform(ProjectStatusLogs $projectStatusLogs)
    {

            $array = [
                'id' => hashid_encode($projectStatusLogs->id),
                'user' =>  $projectStatusLogs->creator->name,
                'icon_url'  => "https://res-crm.papitube.com/image/artist-no-avatar.png", //假头像
                'content' => $projectStatusLogs->content,
                'status' => boolval($projectStatusLogs->status),
                'created_at' => $projectStatusLogs->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,
                'updated_at' => $projectStatusLogs->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,

            ];


        return $array;
    }
//    public function includeUsers(ProjectStatusLogs $projectStatusLogs)
//    {
//        $user = $projectStatusLogs->creator;
//        if (!$user)
//            return null;
//        return $this->item($user, new UserTransformer());
//    }

}
