<?php

namespace App\Http\Transformers;

use App\Models\ProjectStatusLogs;

use League\Fractal\TransformerAbstract;
use phpDocumentor\Reflection\Types\Boolean;

class ProjectCourseTransformer extends TransformerAbstract
{
    protected $availableIncludes = [];


    public function transform(ProjectStatusLogs $projectStatusLogs)
    {

            $array = [
                'id' => hashid_encode($projectStatusLogs->id),
                'user_id' =>  $projectStatusLogs->user_id,
                'content' => $projectStatusLogs->content,
                'status' => boolval($projectStatusLogs->status),
                'created_at' => $projectStatusLogs->created_at->toDatetimeString(),
                'updated_at' => $projectStatusLogs->updated_at->toDatetimeString()

            ];


        return $array;
    }

}
