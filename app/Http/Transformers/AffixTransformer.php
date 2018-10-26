<?php

namespace App\Http\Transformers;

use App\Models\Affix;
use League\Fractal\TransformerAbstract;

class AffixTransformer extends TransformerAbstract
{
    public function transform(Affix $affix)
    {
        return [
//            'user_id',
//            'affixable_id',
//            'affixable_type',
            'title' => $affix->title,
            'url' => $affix->url,
            'type' => $affix->type
        ];
    }
}