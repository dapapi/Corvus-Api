<?php

namespace App\Http\Transformers;

use App\Models\ReviewAnswer;
use League\Fractal\TransformerAbstract;

class ReviewAnswerSumTransformer extends TransformerAbstract{

    protected $availableIncludes = ['creator'];

    private $isAll;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(ReviewAnswer $reviewanswer)
    {
        $array = [

            'truncate'=> $reviewanswer->TRUNCATE,

        ];
        $arraySimple = [
            'id' => hashid_encode($reviewanswer->id),

        ];
        return $this->isAll ? $array : $arraySimple;
    }


}