<?php
namespace App\Helper;

use App\Models\ContractNo;
use Illuminate\Support\Facades\DB;

class Generator
{
    private $init_number = "0001";
    private $boundary = 10000;
    public function generatorBrokerId($key)
    {
        $year = date('Y');
        //获取键对应的合同编号
        $constract_no = ContractNo::where([["key",$key],["year",$year]])->first();
        if($constract_no == null){
            $no = $this->init_number;
            ContractNo::insert(['key'=>$key,'year'=>$year,'no'=>$no]);
        }else{
            $no = intval($constract_no->no)+1;
            print intval($constract_no->no);
            if($no < $this->boundary){
                $no = substr($no+$this->boundary,1,4);
            }
            $constract_no->no = $no;
            DB::connection()->enableQueryLog();
            $constract_no->save();
            $sql = DB::getQueryLog();
            var_dump($sql);
        }
        return $key."-".date('Ymd')."-".$no;
    }

}
