<?php

namespace App\Http\Transformers;

use App\Models\Launch;
use League\Fractal\TransformerAbstract;

class LaunchTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'broker'];

    public function transform(Launch $Launch)
    {


        $array = [
            'id' => hashid_encode($Launch->id),
            'title' => $Launch->title,

            'created_at' => $Launch->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,
            'updated_at' => $Launch->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,


        ];
        $arraySimple = [
            'id' => hashid_encode($Launch->id),
            'name' => $Launch->name,
            'avatar' => $Launch->avatar
        ];

        return $this->isAll ? $array : $arraySimple;;
    }

    public function includeCreator(Launch $star)
    {

        $user = $star->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeBroker(Launch $star)
    {

        $user = $star->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeTasks(Launch $star)
    {

      //  $tasks = $star->tasks()->createDesc()->get();

       // return $this->collection($tasks, new LaunchTransformer());
    }

    public function includeAffixes(Launch $star)
    {

       // $affixes = $star->affixes()->createDesc()->get();
       // return $this->collection($affixes, new AffixTransformer());
    }

}