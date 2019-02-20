<?php

namespace App\Exports;

use App\Models\Trail;
use App\User;
use Exception;
use Illuminate\Support\Facades\DB;
use App\ModuleableType;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrailsExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $request = $this->request;
        $payload =  $request->all();
        return $trails = Trail::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('status') && !is_null($payload['status']))
                $query->where('progress_status', $payload['status']);
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }
            if($request->has('type') && $payload['type'])
                $query->where('type',$payload['type']);

        })
            ->searchData()->poolType()
            //->orderBy('created_at', 'desc')
            ->leftJoin('operate_logs',function($join){
                $join->on('trails.id','operate_logs.logable_id')
                    ->where('logable_type',ModuleableType::TRAIL)
                    ->where('operate_logs.method','4');
            })->groupBy('trails.id')
            ->orderBy('up_time', 'desc')->orderBy('trails.created_at', 'desc')->select(['trails.id','title','brand','principal_id','industry_id','client_id','contact_id','creator_id',
                'type','trails.status','priority','cooperation_type','lock_status','lock_user','lock_at','progress_status','resource','resource_type','take_type','pool_type','receive','fee','desc',
                'trails.updated_at','trails.created_at','pool_type','take_type','receive',DB::raw("max(operate_logs.updated_at) as up_time")]);
//                        $sql_with_bindings = str_replace_array('?', $trails->getBindings(), $trails->toSql());
////
//        dd($sql_with_bindings);
    }

    /**
     * @param Trail $trail
     * @return array
     */
    public function map($trail): array
    {
        $brand = $trail->brand;
        $company = $trail->client->company;
        $grade = $this->type($trail->type);
        $title = $trail->title;
        if (!$trail->principal)
        {
            $principal = '';
        }else{
            $principal = $trail->principal->name;
        }
        $expectations = $trail->bloggerExpectations;
        if (count($expectations) <= 0) {
            $expectations = $trail->expectations;
            if (count($expectations))
                $expectations = $this->starsStr($expectations);
            else
                $expectations= '';
        } else {
            if (count($expectations))

                $expectations = $this->starsStr($expectations);
            else
                $expectations= '';
        }
        $recommendations = $trail->bloggerRecommendations;
        if (count($recommendations) <= 0) {
            $recommendations = $trail->recommendations;
            if (count($recommendations))
                $recommendations = $this->starsStr($recommendations);
            else
                $recommendations = '';
        } else {
            if (count($recommendations))
                $recommendations = $this->starsStr($recommendations);
            else
                $recommendations = '';
        }
        $fee = $trail->fee;
        $resource_type = $this->resourceType($trail->resource_type);
        if ($trail->contact) {
            $contact = $trail->contact->name;
            $phone = $trail->contact->phone . '';
        } else {
            $contact = '';
            $phone = '';
        }
        return [
            $brand,
            $company,
            $grade,
            $title,
            $principal,
            $expectations,
            $recommendations,
            $fee,
           // $resource_type,
            $contact,
            $phone,
        ];
    }

    public function headings(): array
    {
        return [
            '品牌名称',
            '公司名称',
            '线索类型',
            '线索名称',
            '负责人',
            '目标艺人',
            '推荐艺人',
            '预计费用',
           // '线索来源',
            '联系人',
            '联系人电话 '
        ];
    }

    /**
     * @param string $type
     * @return string $type
     */
    private function resourceType($type)
    {
        switch ($type) {
            case 1:
                $type = '商务邮箱';
                break;
            case 2:
                $type = '工作室邮箱';
                break;
            case 3:
                $type = '微信公众号';
                break;
            case 4:
                $type = '员工';
                break;
            case 5:
                $type = '公司高管';
                break;
            case 6:
                $type = '纯中介';
                break;
            case 7:
                $type = '香港中介';
                break;
            case 8:
                $type = '台湾中介';
                break;
            case 9:
                $type = '复购直客';
                break;
            case 10:
                $type = '媒体介绍';
                break;
            case 11:
                $type = '公关or广告公司';
                break;
        }
        return $type;
    }
    private  function type($type)
    {
        switch ($type) {
            case 1:
                $type = '影视线索';
                break;
            case 2:
                $type = '综艺线索';
                break;
            case 3:
                $type = '商务线索';
                break;
            case 4:
                $type = '商务线索';
                break;
        }
        return $type;
    }
    private function starsStr($stars): string
    {
        $starsStr = '';
        foreach ($stars as $star) {
            $starsStr .= $star->name ?? $star->nickname;
            $starsStr .= ',';
        }
        return substr($starsStr, 0, strlen($starsStr) - 1);
    }
    private function principal($principal_name)
    {
        $user = User::where('name', $principal_name)->first();
        if ($user)
            return $user->id;
        else
            return '';
    }
}
