<?php

namespace App\Http\Transformers;

use App\Models\Trail;
use App\Models\TrailArtist;
use App\User;
use League\Fractal\TransformerAbstract;

class TrailTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'client', 'stars', 'contact', 'recommendations'];

    public function transform(Trail $trail)
    {
        $array = [
            'id' => hashid_encode($trail->id),
            'title' => $trail->title,
            'brand' => $trail->brand,
            'resource_type' => $trail->resource_type,
            'type' => $trail->type,
            'status' => $trail->status,
            'progress_status' => $trail->progress_status,
            'desc' => $trail->desc,
        ];

        if ($trail->lock_status)
            $array['fee'] = $trail->fee / 100;

        if ($trail->resource_type == Trail::PERSONAL) {
            $resource = User::where('id', $trail->resource)->first();
            $array['resource'] = [
                'id' => hashid_encode($resource->id),
                'name' => $resource->name,
            ];
        } else {
            $array['resource'] = $trail->resource;
        }

        return $array;
    }

    public function includePrincipal(Trail $trail)
    {
        $principal = $trail->principal;
        if (!$principal)
            return null;

        return $this->item($principal, new UserTransformer());
    }


    public function includeClient(Trail $trail)
    {
        $client = $trail->client;
        if (!$client)
            return null;

        return $this->item($client, new ClientTransformer());
    }

    public function includeContact(Trail $trail)
    {
        $contact = $trail->contact;
        if (!$contact)
            return null;

        return $this->item($contact, new ContactTransformer());
    }

    public function includeRecommendations(Trail $trail)
    {
        $recommendations = $trail->recommendations;

        return $this->collection($recommendations, new ArtistTransformer());
    }

//
//    public function includeStars(Trail $trail)
//    {
//        $stars = $trail->stars;
//
//        return $this->collection($stars, new UserTransformer());
//    }
}