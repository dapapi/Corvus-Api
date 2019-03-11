<?php

namespace App\Http\Transformers;

use App\Models\DataDictionary;
use League\Fractal\TransformerAbstract;

class DataDictionaryTransformer extends TransformerAbstract
{
    public function transform(DataDictionary $dataDictionary)
    {
        if ($dataDictionary->val)
            $arr = [
                'id' =>$dataDictionary->val,
                'name' => $dataDictionary->name,
            ];
        else {
            $arr['enum_value'] = $dataDictionary->name;
        }

        return $arr;
    }
}