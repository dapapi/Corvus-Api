<?php

namespace App\Http\Transformers;

use App\Models\Resource;
use League\Fractal\TransformerAbstract;

class ResourceTransformer extends TransformerAbstract
{
    public function transform(Resource $resource)
    {
        return [
            'id' => hashid_encode($resource->id),
            'title' => $resource->title,
            'code' => $resource->code,
            'type' => $resource->type,
            'status' => $resource->status,
            'desc' => $resource->desc
        ];
    }
}