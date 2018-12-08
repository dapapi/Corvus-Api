<?php

namespace App\Http\Transformers;

use App\Models\Draft;
use League\Fractal\TransformerAbstract;

class DraftTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['project','issues'];

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Draft $draft)
    {
        $array = [
            'id' => hashid_encode($draft->id),
            'template_id' => hashid_encode($draft->template_id),
            'member' => $draft->member,    //平台
            'reviewer_id'=> hashid_encode($draft->reviewer_id),
            'issues_id' => $draft->Answer
        ];
        $arraySimple = [
            'id' => hashid_encode($draft->id),
            'template_id' => hashid_encode($draft->template_id),
            'member' => $draft->member
        ];
        return $this->isAll ? $array : $arraySimple;
    }

    public function includeCreator(Draft $draft)
    {
        $user = $draft->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeProducer(Draft $draft)
    {
        $user = $draft->producer;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeType(Draft $draft)
    {
        $type = $draft->type;
        if (!$type)
            return null;
        return $this->item($type, new BloggerTypeTransformer());
    }
    public function includeIssues(Draft $draft)
    {
        $template = $draft->issues;
        if (!$template)
            return null;
        return $this->item($template, new DraftIssuesAnswerTransformer());
       // $tasks = $draft->issues()->createDesc()->get();
     //   dd($this->collection($tasks, new DraftTransformer()));
     //   return $this->collection($tasks, new DraftIssuesAnswerTransformer());
    }
    public function includeTasks(Draft $draft)
    {
        $tasks = $draft->tasks()->createDesc()->get();
        return $this->collection($tasks, new TaskTransformer());
    }
    public function includeProject(Draft $draft)
    {
        $tasks = $draft->project()->createDesc()->get();
        return $this->collection($tasks, new ProjectTransformer());
    }
    public function includeAffixes(Draft $draft)
    {
        $affixes = $draft->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }

}