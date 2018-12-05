<?php

namespace App\Http\Transformers;

use App\Models\Announcement;
use League\Fractal\TransformerAbstract;

class AnnouncementTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }
    protected $availableIncludes = ['creator', 'tasks', 'affixes', 'broker','report'];

    public function transform(Announcement $announcement)
    {

        $array = [
            'id' => hashid_encode($announcement->id),
            'title' => $announcement->title,  //标题
            'sope' => $announcement->sope,   //公告范围
            'classify' => $announcement->classify,  //分类  1 规则制度   2 内部公告
            'desc' => $announcement->desc, //输入内容
            'readflag' => $announcement->readflag, //默认 0  未读  1 读
            'is_accessory' => $announcement->is_accessory, //是否选择附件  默认  0   无附件    1 有附件
            'accessory' => $announcement->accessory, //附件
            'stick' => $announcement->stick, //默认 0  未读  1 读
            'delete_at' => $announcement->delete_at,
            'created_at' => $announcement->created_at->toDatetimeString(),
            'updated_at' => $announcement->updated_at->toDatetimeString()


        ];


        $arraySimple = [
            'id' => hashid_encode($announcement->id),
            'title' => $announcement->title,
            'desc' => $announcement->desc
        ];

        return $this->isAll ? $array :$arraySimple;
    }

    public function includeCreator(Announcement $announcement)
    {

        $user = $announcement->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeBroker(Announcement $announcement)
    {

        $user = $announcement->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeTasks(Announcement $announcement)
    {

        $tasks = $announcement->tasks()->createDesc()->get();
        return $this->collection($tasks, new AnnouncementTransformer());
    }

    public function includeAffixes(Announcement $announcement)
    {

        $affixes = $announcement->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
}