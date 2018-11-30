<?php

namespace App\Http\Transformers;

use App\Models\Trail;
use App\Models\TrailStar;
use App\User;
use League\Fractal\TransformerAbstract;

class TrailTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'client', 'stars', 'contact', 'recommendations', 'expectations', 'project'];

    public function transform(Trail $trail)
    {
        $array = [
            'id' => hashid_encode($trail->id),
            'title' => $trail->title,
            'brand' => $trail->brand,
            'industry_id' => hashid_encode($trail->industry->id),
            'industry' => $trail->industry->name,
            'resource_type' => $trail->resource_type,
            'resource' => $trail->resource,
            'type' => $trail->type,
            'priority' => $trail->priority,
            'status' => $trail->status,
            'progress_status' => $trail->progress_status,
            'cooperation_type' => $trail->cooperation_type,
            'desc' => $trail->desc,
            'lock_status' => $trail->lock_status,
        ];

        $array['fee'] = $trail->fee / 100;

        if ($trail->resource_type == Trail::PERSONAL) {
            $resource = User::where('id', $trail->resource)->first();
            if ($resource) {
                $array['resource'] = [
                    'id' => hashid_encode($resource->id),
                    'name' => $resource->name,
                ];
            } else {
                $array['resource'] = $trail->resource;
            }
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

        return $this->collection($recommendations, new StarTransformer());
    }


    public function includeExpectations(Trail $trail)
    {
        $expectations = $trail->expectations;

        return $this->collection($expectations, new StarTransformer());
    }

    public function includeProject(Trail $trail)
    {
        $project = $trail->project;
        if (!$project)
            return null;

        return $this->item($project, new ProjectTransformer());
    }
}