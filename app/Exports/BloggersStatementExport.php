<?php

namespace App\Exports;

use App\Models\Blogger;
use Maatwebsite\Excel\Concerns\Exportable;
use App\ModuleableType;
use App\Repositories\ReportFormRepository;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Qiniu\Http\Request;

class BloggersStatementExport implements FromQuery, WithMapping, WithHeadings
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
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $sign_contract_status = $request->get('sign_contract_status',null);
        $department = $request->get('department',null);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->bloggerReport($start_time,$end_time,$sign_contract_status,$department,$target_star);


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
