<?php

namespace App\Http\Transformers;

use App\Models\Report;
use League\Fractal\TransformerAbstract;

class ReportTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'broker'];

    public function transform(Report $star)
    {

        $array = [
            'id' => hashid_encode($star->id),
            'template_name' => $star->template_name,
            'colour' => $star->colour,
            'frequency' => $star->frequency,
            'department_id' => $star->department_id,
            'member' => $star->member,
            'delete_at' => $star->delete_at,
            'created_id' => $star->created_id,
            'created_at' => $star->created_at->toDatetimeString(),
            'updated_at' => $star->updated_at->toDatetimeString(),
            'issues_id' => $star->issues_id,

        ];


        $arraySimple = [
            'id' => hashid_encode($star->id),
            'name' => $star->name,
            'avatar' => $star->avatar
        ];

        return $this->isAll ? $array :$arraySimple;
    }

    public function includeCreator(Report $star)
    {

        $user = $star->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeBroker(Report $star)
    {

        $user = $star->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeTasks(Report $star)
    {

        $tasks = $star->tasks()->createDesc()->get();
        return $this->collection($tasks, new ReportTransformer());
    }

    public function includeAffixes(Report $star)
    {

        $affixes = $star->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
}