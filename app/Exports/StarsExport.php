<?php

namespace App\Exports;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\ModuleableType;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Qiniu\Http\Request;

class ProjectsExport implements FromQuery, WithMapping, WithHeadings
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
        $pageSize = $request->get('page_size', config('app.page_size'));
        $user = Auth::guard("api")->user();
        $userid = $user->id;

        $projects = Project::query()->where(function ($query) use ($request, $payload,$userid) {
            if ($request->has('keyword'))
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }
            if($request->has('administration'))
                $query->where('principal_id','<>' ,$userid);
            if($request->has('principal_id'))
                $query->where('principal_id',$userid);

            if ($request->has('type') && $payload['type'] <> '3,4'){
                $query->where('type', $payload['type']);
            }
            if($request->has('type') && $payload['type'] == '3,4'){
                $query->whereIn('type',[3,4]);
            }
            if ($request->has('status'))
                $query->where('projects.status', $payload['status']);

        })->searchData()
            ->leftJoin('operate_logs',function($join){
                $join->on('projects.id','operate_logs.logable_id')
                    ->where('logable_type',ModuleableType::PROJECT)
                    ->where('operate_logs.method','2');
            })->groupBy('projects.id')
            ->orderBy('operate_logs.updated_at', 'desc')->orderBy('projects.created_at', 'desc')->select(['projects.id','creator_id','project_number','trail_id','title','type','privacy','projects.status',
                'principal_id','projected_expenditure','priority','start_at','end_at','projects.created_at','projects.updated_at','desc']);
         return  $projects;


    }

    /**
     * @param Blogger $blogger
     * @return array
     */
    public function map($project): array
    {

//        $participant = $project->participants()->orderBy('created_at', 'desc')->first();
//        if ($participant) {
//            $contactName = $participant->name;
//            $contactName = $participant->name;
//            $phone = $participant->phone . '';
//            $keyman = $participant->type == 1 ? '是' : '否';
//            $position = $participant->position;
//        } else {
//            $contactName = null;
//            $phone = null;
//            $keyman = null;
//            $position = null;
//        }
//        $nickname = $project->nickname;
//        $platform = $this->plat($blogger->platform);
//        $type = $this->type($blogger->type_id);
//        $communication_status = $this->sign($blogger->communication_status);
//        $intention = $blogger->intention == 1?'是':'否';
//        $sign_contract_other = $blogger->sign_contract_other == 1?'是':'否';
//
//        return [
//            $nickname,
//            $platform,
//            $type,
//            $communication_status,
//            $intention,
//            $sign_contract_other
//
//        ];
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
