<?php

namespace App\Http\Transformers;

use App\Models\Trail;
use League\Fractal\TransformerAbstract;

class TrailTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'client', 'artist', 'contact', 'recommendation'];

    public function transform(Trail $trail)
    {
        $array = [
            'id' => hashid_encode($trail->id),
            'title' => $trail->title,
            'brand' => $trail->brand,
            'principal_id' => hashid_encode($trail->principal_id),
            'client_id' => hashid_encode($trail->client_id),
            'artist_id' => hashid_encode($trail->artist_id),
            'contact_id' => hashid_encode($trail->contact_id),
            'type' => $trail->type,
            'status' => $trail->status,
            'desc' => $trail->desc,
        ];

        return $array;
    }

    public function includePrincipal(Trail $trail)
    {

    }
}