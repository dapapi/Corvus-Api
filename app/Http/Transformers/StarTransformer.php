<?php

namespace App\Http\Transformers;

use App\Models\Star;
use League\Fractal\TransformerAbstract;

class StarTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    protected $availableIncludes = ['creator', 'tasks', 'trails','affixes', 'trails','project','works'];

    public function transform(Star $star)
    {
        $array = [
            'id' => hashid_encode($star->id),
            'name' => $star->name,
            'desc' => $star->desc,
            'avatar' => $star->avatar,
            'gender' => $star->gender,
            'birthday' => $star->birthday,
            'phone' => $star->phone,
            'wechat' => $star->wechat,
            'email' => $star->email,
            'source' => $star->source,
            'communication_status' => $star->communication_status,
            'intention' => boolval($star->intention),
            'intention_desc' => $star->intention_desc,
            'sign_contract_other' => boolval($star->sign_contract_other),
            'sign_contract_other_name' => $star->sign_contract_other_name,
            'sign_contract_at' => $star->sign_contract_at,
            'sign_contract_status' => $star->sign_contract_status,
            'terminate_agreement_at' => $star->terminate_agreement_at,
            'status' => $star->status,
            'type' => $star->type,
            'created_at' => $star->created_at->toDatetimeString(),
            'updated_at' => $star->updated_at->toDatetimeString(),
            'deleted_at' => $star->deleted_at,

            'platform'  =>  $star->platform,
            'weibo_url' =>  $star->weibo_url,
            'weibo_fans_num'  =>  $star->weibo_fans_num,
            'baike_url' =>  $star->baike_url,
            'baike_fans_num'  =>  $star->baike_fans_num,
            'douyin_id' =>  $star->douyin_id,
            'douyin_fans_num' =>  $star->douyin_fans_num,
            'qita_url'  =>  $star->qita_url,
            'qita_fans_num' =>  $star->qita_fans_num,
            'artist_scout_name' =>  $star->artist_scout_name,
            'artist_location' =>  $star->artist_location,

        ];

        $arraySimple = [
            'id' => hashid_encode($star->id),
            'name' => $star->name,
            'avatar' => $star->avatar
        ];

        return $this->isAll ? $array : $arraySimple;
    }

    public function includeCreator(Star $star)
    {
        $user = $star->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeBroker(Star $star)
    {
        $user = $star->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeTasks(Star $star)
    {
        $tasks = $star->tasks()->createDesc()->get();
        return $this->collection($tasks, new TaskTransformer());
    }

    public function includeAffixes(Star $star)
    {
        $affixes = $star->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
    public function includeProject(Star $star)
    {
        $project = $star->project()->get();
        return $this->collection($project,new ProjectTransformer());
    }
    public function includeWorks(Star $star)
    {
     $works = $star->works()->get();
     return $this->collection($works,new WorkTransformer());
    }
    public function includeTrails(Star $star)
    {
        $trails = $star->trail()->get();
        return $this->collection($trails,new TrailTransformer());
    }
}
