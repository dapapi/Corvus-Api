<?php

namespace App\Exports;

use App\Models\Blogger;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Qiniu\Http\Request;

class BloggersExport implements FromQuery, WithMapping, WithHeadings
{

    use Exportable;
    public function __construct($request)
    {
        $this->request = $request;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {

        $array = [];//查询条件
        //合同
        $status = empty($status)?$array[] = ['sign_contract_status',2]:$array[] = ['sign_contract_status',$this->request['status']];
        if( $this->request->has('name')){//姓名
            $array[] = ['nickname','like','%'.$this->request['name'].'%'];
        }

        if($this->request->has('type')){//类型
            $array[] = ['type_id',hashid_decode($this->request['type'])];
        }
        if($this->request->has('communication_status')){//沟通状态
            $array[] = ['communication_status',$this->request['communication_status']];
        }
         return  Blogger::query()->where($array)->searchData()->createDesc();


    }

    /**
     * @param Blogger $blogger
     * @return array
     */
    public function map($blogger): array
    {
        $nickname = $blogger->nickname;
        $platform = $this->plat($blogger->platform);
        $type = $this->type($blogger->type_id);
        $communication_status = $this->sign($blogger->communication_status);
        $intention = $blogger->intention == 1?'是':'否';
        $sign_contract_other = $blogger->sign_contract_other == 1?'是':'否';

        return [
            $nickname,
            $platform,
            $type,
            $communication_status,
            $intention,
            $sign_contract_other

        ];
    }

    public function headings(): array
    {
        return [
            '昵称',
            '平台',
            '类型',
            '沟通状态',
            '与我司签约意向',
            '是否签约其他公司'


        ];
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function sign($type)
    {
        switch ($type) {
            case 1:
                $type = '初步接触';
                break;
            case 2:
                $type = '沟通中';
                break;
            case 3:
                $type = '合同中';
                break;
            case 4:
                $type = '沟通完成';
                break;
        }
        return $type;
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function plat($type)
    {
        switch ($type) {
            case 1:
                $type = '微博';
                break;
            case 2:
                $type = '抖音';
                break;
            case 3:
                $type = '小红书';
                break;
            case 4:
                $type = '全平台';
                break;
        }
        return $type;
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function type($type)
    {
        switch ($type) {
            case 1:
                $type = '搞笑剧情';
                break;
            case 2:
                $type = '美食';
                break;
            case 3:
                $type = '美妆';
                break;
            case 4:
                $type = '颜值';
                break;
            case 5:
                $type = '生活方式';
                break;
            case 6:
                $type = '生活测评';
                break;
            case 7:
                $type = '萌宠';
                break;
            case 8:
                $type = '时尚';
                break;
            case 9:
                $type = '旅行';
                break;
            case 10:
                $type = '动画';
                break;
            case 11:
                $type = '母婴';
                break;
            case 12:
                $type = '情感';
                break;
            case 13:
                $type = '摄影';
                break;
            case 14:
                $type = '舞蹈';
                break;
            case 15:
                $type = '影视';
                break;
            case 16:
                $type = '游戏';
                break;
            case 17:
                $type = '数码';
                break;
            case 18:
                $type = '街访';
                break;
            case 19:
                $type = '其他';
                break;
        }
        return $type;
    }
}
