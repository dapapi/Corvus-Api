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


class TrailTransformer extends TransformerAbstract
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
                'brand' => $trail->brand,
                'industry_id' => is_null($trail->industry) ? null : hashid_encode($trail->industry->id),
                'industry' => is_null($trail->industry) ? null : $trail->industry->name,
                'resource_type' => $trail->resource_type,
                'resource' => $trail->resource,
                'type' => $trail->type,
                'fee' => "".$trail->fee,
                'priority' => $trail->priority,
                'status' => $trail->status,
                'progress_status' => $trail->progress_status,
                'cooperation_type' => $trail->cooperation_type,
                'desc' => $trail->desc,
                'lock_status' => $trail->lock_status,
                // 张峪铭 2019-01-24 20:29  增加锁价人和锁价时间两个字段
              //  'lock_user' => $trail->lock_user,
                'lock_at' => $trail->lock_at,
                // 张峪铭 2019-01-24 20:29  增加锁价人和锁价时间两个字段

                'pool_type'=>$trail->pool_type,
                'take_type'=>$trail->take_type,
                // 日志内容
                'last_follow_up_at' => $trail->last_follow_up_at,
                'last_updated_user' => $trail->last_updated_user,
                'last_updated_at' => $trail->last_updated_at,
                'refused_at' => $trail->refused_at,
                'refused_user' => $trail->refused_user,
                'refused_detail'=> $trail->refused_detail,
                'created_at' => $trail->created_at->toDatetimeString(),//时间去掉秒
                'creator' => $trail->creator->name,
                'power' =>  $trail->power,
                "powers" => $trail->powers,
            ];
           if( $this->project != NULL &&   $this->user != NULL){

            if($this->project->creator_id != $this->user->id && $this->project->principal_id != $this->user->id)
            {
                foreach ($array as $key => $value)
                {
                    $result = PrivacyType::isPrivacy(ModuleableType::TRAIL,$key);
                    if($result)
                    {
                        $result = PrivacyType::excludePrivacy($this->user->id,$this->project->id,ModuleableType::PROJECT, $key);
                        if(!$result)
                        {
                            $array[$key] = 'privacy';
                        }
                    }
                }
            }
          }

        } else {
            $array = [
                'id' => hashid_encode($trail->id),
                'title' => $trail->title,
                'brand' => $trail->brand,
            ];
        }
        //查询跟进信息
        $operate = DB::table('operate_logs as og')//
            ->where('og.logable_id', $trail->id)
            ->select('og.created_at')->orderBy('created_at','desc')->first();

        //查询负责人
        $principal = DB::table('users')//
        ->where('users.id', $trail->principal_id)
            ->select('users.name')->first();
        $array['principal_name'] = $principal;

        //查询艺人

        $starsInfo = DB::table('trail_star')

            ->join('stars', function ($join) {
                $join->on('stars.id', '=', 'trail_star.starable_id');
            })->select('stars.name')
            ->where('trail_star.trail_id', $trail->id)->where('starable_type','star')->get()->toArray();

        $array['stars_name'] = $starsInfo;
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