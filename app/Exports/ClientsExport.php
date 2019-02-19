<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use App\ModuleableType;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsExport implements FromQuery, WithMapping, WithHeadings
{

    use Exportable;
    /**
     * @return \Illuminate\Support\Collection
     */
    public function __construct($request)
    {
        $this->request = $request;
    }


    public function query()
    {
        $request = $this->request;
        $payload =  $request->all();
        $clients = Client::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword'))
                $query->where('company', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('grade'))
                $query->where('grade', $payload['grade']);
            if ($request->has('principal_ids') && count($payload['principal_ids'])) {
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->query()->whereIn('principal_id', $payload['principal_ids']);
            }
        });
        return  $clients->searchData()->leftJoin('operate_logs',function($join){
            $join->on('clients.id','operate_logs.logable_id')
                ->where('logable_type',ModuleableType::CLIENT)
                ->where('operate_logs.method','4');
        })->groupBy('clients.id')
            ->orderBy('up_time', 'desc')->orderBy('clients.created_at', 'desc')->select(['clients.id','company','type','grade','province','city','district',
                'address','clients.status','principal_id','creator_id','client_rating','size','desc','clients.created_at','clients.updated_at','protected_client_time',
                DB::raw("max(operate_logs.updated_at) as up_time")]);

//        $sql_with_bindings = str_replace_array('?', $clients->getBindings(), $clients->toSql());
//        dd($sql_with_bindings);


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
