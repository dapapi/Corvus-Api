<?php

namespace App\Http\Transformers;

use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\PrivacyType;
use App\User;
use League\Fractal\ParamBag;
use Illuminate\Support\Facades\Auth;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;


class TrailClientTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'client', 'stars', 'contact', 'recommendations',
        'expectations', 'project','starexpectations','bloggerexpectations','starrecommendations','bloggerrecommendations','lockuser'];
   // protected $defaultIncludes= ['stars'];
    private $isAll = true;
  //  private $setprivacy = true;

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
                'status' => $trail->status,
                'client_id' => $trail->client_id,
                'progress_status' => $trail->progress_status,
                'created_at' => $trail->created_at->toDatetimeString(),//时间去掉秒
                // 日志内容
                'power' => $trail->power,
            ];

            //查询跟进信息
            $operate = DB::table('operate_logs as og')//
            ->where('og.logable_id', $trail->id)
                ->select('og.created_at')->orderBy('created_at', 'desc')->first();

            //查询负责人
            $principal = DB::table('users')//
            ->where('users.id', $trail->principal_id)
                ->select('users.name')->first();
            $array['principal']['data']['id'] = hashid_encode($trail->principal_id);
            $array['principal']['data']['name'] = $principal->name;

            $trails = DB::table('trails')
                ->join('clients', function ($join) {
                    $join->on('trails.client_id', '=', 'clients.id');
                })
                ->where('clients.id', $trail->client_id)
                ->select('clients.id','clients.company')->first();

            if($trails){
                $array['client']['data']['id'] = hashid_encode($trails->id);
                $array['client']['data']['company'] = $trails->company;

            }else{
                $array['client']['data']['id'] = '';
                $array['client']['data']['company'] = '';
            }

            return $array;
        }


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

        return $this->item($client, new ClientTransformer());
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