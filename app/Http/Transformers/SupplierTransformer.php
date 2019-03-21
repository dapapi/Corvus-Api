<?php

namespace App\Http\Transformers;

use App\Models\Supplier;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;


class SupplierTransformer extends TransformerAbstract
{
    private  $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Supplier $supplier)
    {
        if ($this->isAll) {
            $array = [
                'id' => hashid_encode($supplier->id),
                'name' => $supplier->name,
                'code' => $supplier->code,
                'create_id' => $supplier->create_id,
                'address' => $supplier->address,
                'level' => $supplier->level,
            ];

            $res = DB::table('users')->where('users.id', $supplier->create_id)->select('name')->first();

            if (!empty($res)) {
                $array['create_name'] = $res->name;
                $array['operate_time'] = '2018-10-15 00:00:00';

            }else{
                $array['create_name'] = '';
            }
        }

        return $array;
    }
}