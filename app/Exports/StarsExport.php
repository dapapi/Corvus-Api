<?php

namespace App\Exports;
use Illuminate\Support\Facades\Auth;
use App\Models\Star;
use App\ModuleableType;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Qiniu\Http\Request;

class StarsExport implements FromQuery, WithMapping, WithHeadings
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
        $request = $this->request;
        $payload = $request->all();
        $array = [];//查询条件
        if ($request->has('name')) {//姓名
            $array[] = ['name', 'like', '%' . $payload['name'] . '%'];
        }
        if ($request->has('sign_contract_status') && !empty($payload['sign_contract_status'])) {//签约状态
            $array[] = ['sign_contract_status', $payload['sign_contract_status']];
        }
        if ($request->has('communication_status') && !empty($payload['communication_status'])) {//沟通状态
            $array[] = ['communication_status', $payload['communication_status']];
        }
        if ($request->has('source') && !empty($payload['source'])) {//艺人来源
            $array[] = ['source', $payload['source']];
        }
        $stars = Star::query()->createDesc()
            ->searchData()
            ->where($array);//根据条件查询
         return  $stars;


    }

    /**
     * @param Blogger $blogger
     * @return array
     */
    public function map($star): array
    {

        $name = $star->name;
        $gender = $star->gender == 1 ? '男':'女';
        $birthday = $star->birthday;
        $source = $this->source($star->source);
        $phone= $star->phone;
        $eamail = $star->eamail;
        $platform = $this->platform($star->platform);
        $artist_scout_name = $star->artist_scout_name;
        $sign_contract_other = $star->sign_contract_other == 1?'是':'否';
        return [
            $name,
            $gender,
            $birthday,
            $source,
            $phone,
            $eamail,
            $platform,
            $artist_scout_name,
            $sign_contract_other

        ];
    }

    public function headings(): array
    {
        return [
            '姓名',
            '性别',
            '出生日期',
            '艺人来源',
            '手机号',
            '邮箱',
            '社交平台',
            '星探',
            '地区',
            '沟通状态',
            '与我司签约意向',
            '是否签约其他公司'


        ];
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function source($source)
    {
        switch ($source) {
            case 1:
                $source = '线上';
                break;
            case 2:
                $source = '线下';
                break;
            case 3:
                $source = '抖音';
                break;
            case 4:
                $source = '微博';
                break;
            case 5:
                $source = '陈赫';
                break;
            case 6:
                $source = '北电';
                break;
            case 7:
                $source = '杨光';
                break;
            case 8:
                $source = '中戏';
                break;
            case 9:
                $source = 'papitube推荐';
                break;
            case 10:
                $source = '地标商圈';
                break;
        }
        return $source;
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function platform($platform)
    {
        switch ($platform) {
            case 1:
                $platform = '微博';
                break;
            case 2:
                $platform = '抖音';
                break;
            case 3:
                $platform = '小红书';
                break;
            case 4:
                $platform = '全平台';
                break;
        }
        return $platform;
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
