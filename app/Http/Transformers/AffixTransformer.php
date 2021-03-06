<?php

namespace App\Http\Transformers;

use App\Models\Affix;
use League\Fractal\TransformerAbstract;

class AffixTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['user'];

    public function transform(Affix $affix)
    {
        return [
            'id' => hashid_encode($affix->id),
            'title' => $affix->title,
            'url' => $affix->url.'?attname='.$affix->title,
            'size' => $affix->size,
            'type' => $affix->type,
            'created_at' => $affix->created_at->toDateTimeString(),//时间去掉秒,,
//            'updated_at' => $affix->updated_at->toDatetimeString(),

        ];
    }

    public function includeUser(Affix $affix)
    {
        $user = $affix->user;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
}