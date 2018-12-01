<?php

namespace App\Http\Transformers;

use App\Models\DraftIssuesAnswer;
use League\Fractal\TransformerAbstract;

class DraftIssuesAnswerTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['project'];

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(DraftIssuesAnswer $draftissuesanswer)
    {
        $array = [
            'id' => hashid_encode($draftissuesanswer->id),
            'template_id' => hashid_encode($draftissuesanswer->template_id),
            'member' => $draftissuesanswer->member,    //平台
            'issues' => $draftissuesanswer->issues
        ];
        $arraySimple = [
            'id' => hashid_encode($draftissuesanswer->id),
            'template_id' => hashid_encode($draftissuesanswer->template_id),
            'member' => $draftissuesanswer->member
        ];
        return $this->isAll ? $array : $arraySimple;
    }

    public function includeCreator(DraftIssuesAnswer $draftissuesanswer)
    {
        $user = $draftissuesanswer->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeProducer(DraftIssuesAnswer $draftissuesanswer)
    {
        $user = $draftissuesanswer->producer;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeType(DraftIssuesAnswer $draftissuesanswer)
    {
        $type = $draftissuesanswer->type;
        if (!$type)
            return null;
        return $this->item($type, new BloggerTypeTransformer());
    }
    public function includetIssues(DraftIssuesAnswer $draftissuesanswer)
    {
        $template = $draftissuesanswer->issues;
        if (!$template)
            return null;
        return $this->item($template, new DraftIssuesAnswerTransformer());
    }
    public function includeTasks(DraftIssuesAnswer $draftissuesanswer)
    {
        $tasks = $draftissuesanswer->tasks()->createDesc()->get();
        return $this->collection($tasks, new TaskTransformer());
    }
    public function includeProject(DraftIssuesAnswer $draftissuesanswer)
    {
        $tasks = $draftissuesanswer->project()->createDesc()->get();
        return $this->collection($tasks, new ProjectTransformer());
    }
    public function includeAffixes(DraftIssuesAnswer $draftissuesanswer)
    {
        $affixes = $draftissuesanswer->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }

}