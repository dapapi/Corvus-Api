<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsExport implements FromQuery, WithMapping, WithHeadings
{

    use Exportable;
    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        return Client::query();
    }

    /**
     * @param Client $client
     * @return array
     */
    public function map($client): array
    {
        $type = $this->type($client->type);
        $company = $client->company;
        $grade = $client->grade == 1 ? '直客' : '代理公司';
        $principal = $client->principal;
        if ($principal)
            $principalName = $principal->name;
        else
            $principalName = null;
        $contact = $client->contacts()->orderBy('created_at', 'desc')->first();

        $size = $client->size == Client::SIZE_LISTED ? '上市公司' : '500强';
        if ($contact) {
            $contactName = $contact->name;
            $phone = $contact->phone . '';
            $keyman = $contact->type == 1 ? '是' : '否';
            $position = $contact->position;
        } else {
            $contactName = null;
            $phone = null;
            $keyman = null;
            $position = null;
        }
        return [
            $type,
            $company,
            $grade,
            $principalName,
            $contactName,
            $phone,
            $keyman,
            $position,
            $size
        ];
    }

    public function headings(): array
    {
        return [
            '客户类型',
            '公司名称',
            '级别',
            '负责人',
            '联系人',
            '联系人电话',
            '决策关键人',
            '职位',
            '规模'
        ];
    }

    /**
     * @param string $type
     * @return string $type
     */
    private function type($type)
    {
        switch ($type) {
            case 1:
                $type = '影视客户';
                break;
            case 2:
                $type = '综艺客户';
                break;
            case 3:
                $type = '商务代言';
                break;
            case 4:
                $type = 'papi客户';
                break;
        }
        return $type;
    }
}
