<?php

namespace App\Http\Transformers;

use App\Models\Production;
use League\Fractal\TransformerAbstract;

class ProductionTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'producer'];

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Production $production)
    {
        $array = [
            'id' => hashid_encode($production->id),
            'nickname' => $production->nickname,
            'videoname' => $production->videoname,
            'release_time' => $production->release_time,
            'read_proportion' => $production->read_proportion,
            'link' => boolval($production->link),
            'advertising' => $production->advertising

        ];

        return $this->isAll ? $array : '';
    }

    public function includeCreator(Production $production)
    {
        $user = $production->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }


//    public function includeProducer(Blogger $blogger)
//    {
//        $producer = $blogger->producer;
//        if (!$producer)
//            return null;
//        return $this->item($producer, new BloggerProducerTransformer());
//    }
    public function includeTasks(Production $production)
    {
        $tasks = $production->tasks()->createDesc()->get();
        return $this->collection($tasks, new TaskTransformer());
    }
    public function includeProject(Production $production)
    {
        $tasks = $production->project()->createDesc()->get();
        return $this->collection($tasks, new ProjectTransformer());
    }
    public function includeAffixes(Production $production)
    {
        $affixes = $production->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }

}