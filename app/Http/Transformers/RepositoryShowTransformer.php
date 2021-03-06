<?php

namespace App\Http\Transformers;

use App\Models\Repository;
use League\Fractal\TransformerAbstract;

class RepositoryShowTransformer extends TransformerAbstract
{

    private $isAll;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }
    protected $availableIncludes = ['scope','creator'];

    public function transform(Repository $repository)
    {

        $array = [
            'id' => hashid_encode($repository->id),
            'title' => $repository->title,  //标题
            'scope' => $repository->scope,  //对象id
            'desc' => $repository->desc, //输入内容
            'creator_id' => hashid_encode($repository->creator_id),//创建人id
            'accessory' =>$repository->accessory,
            'is_accessory' =>$repository->is_accessory,
            'stick' => $repository->stick, //是否选择置顶  默认  0   无附件    1 有附件
            'comments_no' => $repository->comments_no,
            'delete_at' => $repository->delete_at,
            'created_at' => $repository->created_at->toDatetimeString(),
            'updated_at' => $repository->updated_at->toDatetimeString()


        ];


        $arraySimple = [
            'id' => hashid_encode($repository->id),
            'title' => $repository->title,
            'desc' => $repository->desc
        ];

        return $this->isAll ? $array :$arraySimple;
    }

    public function includeCreator(Repository $repository)
    {

        $user = $repository->creator;

        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeBroker(Repository $repository)
    {

        $user = $repository->broker;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeScope(Repository $repository)
    {
        $scope = $repository->scope()->createDesc()->get();

        return $this->collection($scope, new AnnouncementScopeTransformer());
    }

    public function includeAffixes(Repository $repository)
    {

        $affixes = $repository->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }

}