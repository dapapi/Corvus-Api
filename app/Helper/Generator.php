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
            if($no < $this->boundary){
                $no = substr($no+$this->boundary,1,4);
            }
            $constract_no->no = $no;
            $constract_no->save();
        }
        return $key."-".date('Ymd')."-".$no;
    }

    /**
     * @param $key 前缀
     * @param $number 后缀位数
     * @param bool $is_new_code 是否重新编码
     * @return string   返回编码
     * @author lile
     * @date 2019-03-21 16:47
     */
    public function generatorCode($key,$number,$is_new_code=false)
    {
        $year = date('Y');
        //获取合同编号
        $constract_no = ContractNo::where([["key",$key],["year",$year]])->first();
        if ($constract_no == null){//判断是否存在合同编号
            //如果不存在则判断，判断是否需要重新编码
            if ($is_new_code){//如果需要重新编码，则从0开始
                $no = sprintf("%0{$number}s",1);
                ContractNo::insert(['key'=>$key,'year'=>$year,'no'=>$no]);
            }else{//如果不需要则获取上一年的编码，继续增加，那么只根据$key找到最大的编码继续递增
                $no = ContractNo::where("key",$key)->orderBy('no','desc')->value('no');
                $no = intval($no)+1;
                if($no < $this->boundary){
                    $no = sprintf("%0{$number}s",$no);
                }
                ContractNo::insert(['key'=>$key,'year'=>$year,'no'=>$no]);
            }
        }else{//合同编码存在则继续新增
            $no = intval($constract_no->no)+1;
            if($no < $this->boundary){
                $no = sprintf("%0{$number}s",$no);
            }
            $constract_no->no = $no;
            $constract_no->save();
        }
        return $key.$year.$no;

    }

}
