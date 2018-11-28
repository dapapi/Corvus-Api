<?php

namespace App\Http\Transformers;

use App\Models\BloggerProducer;
use League\Fractal\TransformerAbstract;

class BloggerProducerTransformer extends TransformerAbstract
{

    public function transform(BloggerType $bloggerType)
    {
        return [
            'id' => hashid_encode($bloggerType->id),
            'name' => $bloggerType->name,
        ];
    }


}