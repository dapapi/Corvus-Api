<?php

namespace App\Http\Transformers;

use App\Models\Issues;
use League\Fractal\TransformerAbstract;


class IssuesTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'broker'];

    public function transform(Issues $Issues)
    {
        $array = [
            'id' => hashid_encode($Issues->id),
            'issues' => $Issues->issues,
            //'department_id' => $Issues->department_id,
            'member_id' => $Issues->member_id,
            'task_id' => $Issues->task_id,
            'accessory' => $Issues->accessory,
            'type' =>$Issues->type,
            'required' =>$Issues->required,
            'created_at' => $Issues->created_at->toDatetimeString(),
            'updated_at' => $Issues->updated_at->toDatetimeString()


        ];
        $arrayanswer = [

            'id' => hashid_encode($Issues->id),
            'issues' => $Issues->issues,
            //'department_id' => $Issues->department_id,
            'member_id' => $Issues->member_id,
            'task_id' => $Issues->task_id,

            'answer' => $Issues->answer,
            'accessory' => $Issues->accessory,
            'type' =>$Issues->type,
            'required' =>$Issues->required,
            'created_at' => $Issues->created_at->toDatetimeString(),
            'updated_at' => $Issues->updated_at->toDatetimeString()


        ];

        return $this->isAll ?$arrayanswer : $array ;
    }

    public function includeCreator(Issues $Issues)
    {

        $user = $Issues->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeBroker(Issues $Issues)
    {

        $user = $Issues->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeTasks(Issues $Issues)
    {

        $tasks = $Issues->tasks()->createDesc()->get();
        return $this->collection($tasks, new ReportTransformer());
    }

    public function includeAffixes(Issues $Issues)
    {

        $affixes = $Issues->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
}