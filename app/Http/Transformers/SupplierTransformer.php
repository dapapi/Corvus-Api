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
                'create_time' => $supplier->created_at->toDatetimeString(),
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

            $info = DB::table('supplier_relates')->where('supplier_id', $supplier->id)->where('type',1)->select('key as name','value as account','currency')->get()->toArray();
            if (!empty($info)) {
                $array['bank'] = $info;
            }else{
                $array['bank'] = '';
            }

            $contactInfo = DB::table('supplier_relates')->where('supplier_id', $supplier->id)->where('type',2)->select('key','value')->get()->toArray();
            if (!empty($info)) {
                $array['contact'] = $contactInfo;
            }else{
                $array['contact'] = '';
            }
        }

        return $array;
    }
}