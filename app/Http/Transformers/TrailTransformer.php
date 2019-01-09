<?php

namespace App\Http\Transformers;

use App\Models\Trail;
use App\Models\TrailStar;
use App\User;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class TrailTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'client', 'stars', 'contact', 'recommendations', 'expectations', 'project'];

    private $isAll = true;
    private $setprivacy = true;
    public function __construct($isAll = true,$setprivacy = true)
    {
        $this->isAll = $isAll;
        $this->setprivacy = $setprivacy;
    }


    public function transform(Trail $trail)
    {
        if ($this->isAll) {
            if($this->setprivacy){
                $array = [
                    'id' => hashid_encode($trail->id),
                    'title' => $trail->title,
                    'brand' => $trail->brand,
                    'industry_id' => hashid_encode($trail->industry->id),
                    'industry' => $trail->industry->name,
                    'resource_type' => $trail->resource_type,
                    'type' => $trail->type,
                    'fee' => $trail->fee,
                    'priority' => $trail->priority,
                    'status' => $trail->status,
                    'progress_status' => $trail->progress_status,
                    'cooperation_type' => $trail->cooperation_type,
                    'desc' => $trail->desc,
                    'lock_status' => $trail->lock_status,
                    // 日志内容
                    'last_follow_up_at' => $trail->last_follow_up_at,
                    'last_updated_user' => $trail->last_updated_user,
                    'last_updated_at' => $trail->last_updated_at,
                    'refused_at' => $trail->refused_at,
                    'refused_user' => $trail->refused_user,
                    'created_at' => $trail->created_at->toDateTimeString(),
                    'creator' => $trail->creator->name,
                ];
            }else{
                $array = [
                    'id' => hashid_encode($trail->id),
                    'title' => $trail->title,
                    'brand' => $trail->brand,
                    'industry_id' => hashid_encode($trail->industry->id),
                    'industry' => $trail->industry->name,
                    'resource_type' => $trail->resource_type,
                    'type' => $trail->type,
                    'priority' => $trail->priority,
                    'status' => $trail->status,
                    'progress_status' => $trail->progress_status,
                    'cooperation_type' => $trail->cooperation_type,
                    'desc' => $trail->desc,
                    'lock_status' => $trail->lock_status,
                    // 日志内容
                    'last_follow_up_at' => $trail->last_follow_up_at,
                    'last_updated_user' => $trail->last_updated_user,
                    'last_updated_at' => $trail->last_updated_at,
                    'refused_at' => $trail->refused_at,
                    'refused_user' => $trail->refused_user,
                    'created_at' => $trail->created_at->toDateTimeString(),
                    'creator' => $trail->creator->name,
                ];
            }
            if(in_array('fee',$array)){
                $resource = User::where('id', $trail->resource)->first();
                dd($trail);
            }
            if (is_numeric($trail->resource)) {
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
        } else {
            $array = [
                'id' => hashid_encode($trail->id),
                'title' => $trail->title,
                'brand' => $trail->brand,
            ];
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
        $recommendations = $trail->bloggerRecommendations;
        if (count($recommendations) <= 0) {
            $recommendations = $trail->recommendations;
            return $this->collection($recommendations, new StarTransformer());
        } else {
            return $this->collection($recommendations, new BloggerTransformer());
        }

    }

    public function includeExpectations(Trail $trail)
    {
        $expectations = $trail->bloggerExpectations;
        if (count($expectations) <= 0) {
            $expectations = $trail->expectations;
            return $this->collection($expectations, new StarTransformer());
        } else {
            return $this->collection($expectations, new BloggerTransformer());
        }
    }

    public function includeProject(Trail $trail)
    {
        $project = $trail->project;
        if (!$project)
            return null;

        return $this->item($project, new ProjectTransformer());
    }

    public function includeCompletedProject(Trail $trail)
    {
        $project = $trail->completed()->project;
        if (!$project)
            return null;

        return $this->item($project, new ProjectTransformer());
    }

}