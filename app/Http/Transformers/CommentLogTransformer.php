<?php

namespace App\Http\Transformers;

use App\Models\CommentLog;
use League\Fractal\TransformerAbstract;

class CommentLogTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['user','parent','tocompany'];

    public function transform(CommentLog $commentLog)
    {
        $array = [
            'id' => hashid_encode($commentLog->id),
            'parent_id' => hashid_encode($commentLog->parent_id),
            'content' => $commentLog->content,
            'method' => $commentLog->method,
            'status' => $commentLog->status,
            'level' => $commentLog->level,
            'created_at' => $commentLog->created_at->toDatetimeString(),
            'user' => hashid_encode($commentLog->user_id),
        ];

        if ($commentLog->user_id) {
            $array['username'] = $commentLog->user->name;
        }
        return $array;
    }

    public function includeUser(CommentLog $commentLog)
    {
        $user = $commentLog->user;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeParent(CommentLog $commentLog)
    {

      //  $trails = $commentLog->childCategory;


        $trails = $commentLog->parent;
       return $this->collection($trails,new CommentLogTransformer());
    }
    public function includeToCompany(CommentLog $commentLog)
    {

        //  $trails = $commentLog->childCategory;


        $trails = $commentLog->tocompany;
        return $this->collection($trails,new CommentLogTransformer());
    }
}