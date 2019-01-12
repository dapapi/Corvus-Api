<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;

class StarAndBloggerTransfromer extends TransformerAbstract
{
    public function transform($model)
    {
        return [
            'id'    =>  hashid_encode($model->id),
            'name'  =>  $model->nickname,
            'flag' =>  $model->flag
        ];
    }
}