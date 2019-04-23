<?php

namespace App\Http\Transformers;



use App\ModuleableType;
use App\Models\Trail;
use App\Models\Blogger;
use App\Models\Star;
use App\Models\Client;
use App\Models\User;
use App\Models\TrailStar;

use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;

class TrailIndexOneTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'client', 'stars', 'contact', 'recommendations',
        'expectations', 'project','starexpectations','bloggerexpectations','starrecommendations','bloggerrecommendations','lockuser'];

    private $isAll = true;

    private $project;
    private $user;


    public function __construct($isAll = true, $project = null, $user = null)
    {
        $this->isAll = $isAll;
        $this->project = $project;
        $this->user = $user;

    }


    public function transform(Trail $trail)
    {
        if ($this->isAll) {

            $array = [
                'id' => hashid_encode($trail->id),
                'title' => $trail->title,
                'status'=> $trail->status,
                'principal_id'=>$trail->principal_id,
                'last_follow_up_at_or_created_at' => $trail->up_time,

            ];

        } else {
            $array = [
                'id' => hashid_encode($trail->id),
                'title' => $trail->title,
                'brand' => $trail->brand,
            ];
        }
        //查询负责人
        $principal =  User::where('users.id', $trail->principal_id)
            ->select('users.name')->first();
        $array['principal_name'] = $principal['name'];

        //查询艺人
        //客户字段
//        'client.customer','client.brand',
        $client = Client::where('clients.id', $trail->client_id)
            ->select('customer','brand')->first();
        $array['customer'] = $client['customer'];
        $array['brand'] = $client['brand'];

        $star = TrailStar::where('trail_star.trail_id', $trail->id)->where('type',TrailStar::EXPECTATION)->first(['starable_id','starable_type']);
        if($star) {
            if ($star['starable_type'] == ModuleableType::BLOGGER)
            {
                $starsInfo = Blogger::where('id',$star['starable_id'])->select('bloggers.nickname as name')->first()->toArray();
            }else if($star['starable_type'] == ModuleableType::STAR){
                $starsInfo = Star::where('id',$star['starable_id'])->select('stars.name as name' )->first()->toArray();
            }else{
                $starsInfo['name'] = '';

            }
        }
        else
            $starsInfo['name'] = '';
        $array['stars_name'] = $starsInfo['name'];
        return $array;

    }

    public function includePrincipal(Trail $trail)
    {
        $principal = $trail->principal;
        if (!$principal)
            return null;

        return $this->item($principal, new UserTransformer());
    }

    public function includeLockUser(Trail $trail)
    {
        $lockUser = $trail->lockUser;
        if (!$lockUser)
            return null;

        return $this->item($lockUser, new UserTransformer());
    }

    public function includeClient(Trail $trail)
    {
        $client = $trail->client;
        if (!$client)
            return $this->null();

        return $this->item($client, new ClientIndexTransformer());
    }

    public function includeContact(Trail $trail)
    {
        $contact = $trail->contact;
        if (!$contact)
            return $this->null();

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

    public function includeStarExpectations(Trail $trail)
    {
        return $this->collection($trail->starExpectations, new StarTransformer(false));
    }
    public function includeBloggerExpectations(Trail $trail)
    {
        return $this->collection($trail->bloggerExpectations, new BloggerTransformer(false));
    }
    public function includeStarrecommendations(Trail $trail)
    {
        return $this->collection($trail->starRecommendations,new StarTransformer(false));
    }
    public function includeBloggerrecommendations(Trail $trail)
    {
        return $this->collection($trail->bloggerRecommendations,new BloggerTransformer(false));
    }



}