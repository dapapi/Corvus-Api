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
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'broker','report','bulletinreview'];

    public function transform(Report $report)
    {

        $array = [
            'id' => hashid_encode($report->id),
            'template_name' => $report->template_name,
            'colour' => $report->colour,
            'frequency' => $report->frequency,
            'department_id' => $report->department_id,
            'member' => $report->member,
            'status' =>$report->status,
           // 'review_id' => $report->status['id'],
            'delete_at' => $report->delete_at,
           // 'created_id' => $report->created_id,
            'created_at' => $report->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,
            'updated_at' => $report->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,
            //'issues_id' => $report->issues_id,

        ];


        $arraySimple = [
            'id' => hashid_encode($report->id),
            'name' => $report->name,
            'avatar' => $report->avatar
        ];

        return $this->isAll ? $array :$arraySimple;
    }

    public function includeCreator(Report $report)
    {

        $user = $report->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeBroker(Report $report)
    {

        $user = $report->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeTasks(Report $report)
    {

        $tasks = $report->tasks()->createDesc()->get();
        return $this->collection($tasks, new ReportTransformer());
    }
    public function includeBulletinReview(BulletinReview $bulletinreview)
    {

        $tasks = $bulletinreview->bulletinreview()->createDesc()->get();
        return $this->collection($tasks, new ReviewTransformer());
    }
    public function includeAffixes(Report $report)
    {

        $affixes = $report->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
}