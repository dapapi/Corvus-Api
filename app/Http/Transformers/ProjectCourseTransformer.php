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
                'content' => $projectStatusLogs->content,
                'status' => boolval($projectStatusLogs->status),
                'created_at' => $projectStatusLogs->created_at->toDatetimeString(),
                'updated_at' => $projectStatusLogs->updated_at->toDatetimeString()

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
