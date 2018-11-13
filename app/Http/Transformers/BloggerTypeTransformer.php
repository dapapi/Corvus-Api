<?php

namespace App\Http\Transformers;

use App\Models\BloggerType;
use League\Fractal\TransformerAbstract;

class BloggerTypeTransformer extends TransformerAbstract
{

    public function transform(BloggerType $bloggerType)
    {
        return [
            'id' => hashid_encode($bloggerType->id),
            'name' => $bloggerType->name,
        ];
    }


}