<?php

namespace App\Http\Transformers;

use App\Models\BloggerCommunication;
use League\Fractal\TransformerAbstract;

class BloggerCommunicationTransformer extends TransformerAbstract
{

    public function transform(BloggerCommunication $bloggercommunication)
    {
        return [
            'id' => hashid_encode($bloggercommunication->id),
            'name' => $bloggercommunication->name,
        ];
    }


}