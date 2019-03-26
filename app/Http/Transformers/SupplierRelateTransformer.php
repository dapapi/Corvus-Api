<?php

namespace App\Http\Transformers;

use App\Models\SupplierRelate;
use League\Fractal\TransformerAbstract;

class SupplierRelateTransformer extends TransformerAbstract
{

    public function transform(SupplierRelate $supplierRelate)
    {
        $array = [
            'id' => hashid_encode($supplierRelate->id),
            'key' =>$supplierRelate->key,
            'value' =>$supplierRelate->value,
        ];

        return $array;
    }


}