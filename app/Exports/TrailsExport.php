<?php

namespace App\Exports;

use App\Models\Trail;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrailsExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public function query()
    {
        return Trail::query();
    }

    /**
     * @param Trail $trail
     * @return array
     */
    public function map($trail): array
    {
        $brand = $trail->brand;
        $company = $trail->client->company;
        $grade = $trail->client->grade == 1 ? '直客' : '代理公司';
        $title = $trail->title;
        $principal = $trail->principal->name;

        if (count($trail->expectations))
            $expectations = $this->starsStr($trail->expectations);
        else
            $expectations= '';

        if (count($trail->recommendations))
            $recommendations = $this->starsStr($trail->recommendations);
        else
            $recommendations = '';

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
            $resource_type,
            $contact,
            $phone,
        ];
    }

    public function headings(): array
    {
        return [
            '品牌名称',
            '公司名称',
            '级别',
            '线索名称',
            '负责人',
            '目标艺人',
            '推荐艺人',
            '预计费用',
            '线索来源',
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

    private function starsStr($stars): string
    {
        $starsStr = '';
        foreach ($stars as $star) {
            $starsStr .= $star->name ?? $star->nickname;
            $starsStr .= ',';
        }
        return substr($starsStr, 0, strlen($starsStr) - 1);
    }
}
