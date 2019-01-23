<?php

namespace App\Http\Transformers;

use App\Models\ContractPapi;
use League\Fractal\TransformerAbstract;

class ContractPapiTransformer extends TransformerAbstract
{

    protected $availableIncludes = ['creator', 'tasks', 'affixes'];

    private $isAll;


    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(ContractPapi $contractPapi)
    {
        $array = [
            'id' => hashid_encode($contractPapi->id),
            'nickname' => $contractPapi->nickname,//合同编号
            'contract_no' => $contractPapi->contract_no,
            'type_id' => $contractPapi->type_id,//合同类型
            'contract_company' => $contractPapi->contract_company,//合同公司
          //  'creator' => $contractPapi->creator,//姓名
            'approval_status' => $contractPapi->approval_status,
            'contract_name' => $contractPapi->contract_name,//合同名称
            'treaty_particulars' => $contractPapi->treaty_particulars,//合同摘要
            'business_id' => $contractPapi->business_id,//业务类型
            'contract_start_date' => $contractPapi->contract_start_date,//合约起始日
            'contract_end_date' => $contractPapi->contract_end_date,//合约终止日
            'earnings' => $contractPapi->earnings,//收益分配比例
            'certificate_id' => $contractPapi->certificate_id,//证件类别
            'certificate_number' => $contractPapi->certificate_number,//certificate_number
            'certificate_affix' => $contractPapi->certificate_affix,//certificate_affix_id
            'scanning_affix' => $contractPapi->scanning_affix,//scanning_affix_id
            'scanning' => $contractPapi->scanning,//份数
            'contract_affix' => $contractPapi->contract_affix,//附件类别
            'created_at'=> $contractPapi->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,, //创建时间
            'updated_at' => $contractPapi->updated_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,
        ];
        $arraySimple = [
            'id' => hashid_encode($contractPapi->id),
            'nickname' => $contractPapi->nickname,
            'contract_no' => $contractPapi->contract_no
        ];
        return $this->isAll ? $array : $arraySimple;
    }

    public function includeCreator(ContractPapi $contractPapi)
    {
        $user = $contractPapi->creator;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }

    public function includeProducer(ContractPapi $contractPapi)
    {
        $user = $contractPapi->producer;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
    public function includeType(ContractPapi $contractPapi)
    {
        $type = $contractPapi->type;
        if (!$type)
            return null;
        return $this->item($type, new BloggerTypeTransformer());
    }
//    public function includeProducer(Blogger $blogger)
//    {
//        $producer = $blogger->producer;
//        if (!$producer)
//            return null;
//        return $this->item($producer, new BloggerProducerTransformer());
//    }
    public function includeTrails(ContractPapi $contractPapi)
    {
        $trails = $contractPapi->trail()->get();
        return $this->collection($trails,new TrailTransformer());
    }

    public function includeTasks(ContractPapi $contractPapi)
    {
        $tasks = $contractPapi->tasks()->createDesc()->get();
        return $this->collection($tasks, new TaskTransformer());
    }

    public function includeAffixes(ContractPapi $contractPapi)
    {
        $affixes = $contractPapi->affixes()->createDesc()->get();
        return $this->collection($affixes, new AffixTransformer());
    }
    public function includePublicity(ContractPapi $contractPapi){
        $users = $contractPapi->publicity()->get();

        return $this->collection($users,new UsersTransformer());
    }

}