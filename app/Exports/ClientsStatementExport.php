<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use App\Repositories\ReportFormRepository;
use App\ModuleableType;
use App\Models\Contact;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsStatementExport implements FromQuery, WithMapping, WithHeadings
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
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        $arr[] = ['c.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['c.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($type != null){
            $arr[] = ['c.type',$type];
        }
       return $clients = (new Client())->setTable('c')->from('clients as c')
            ->leftJoin('users as u','u.id','=','c.principal_id')
            ->leftJoin('contacts as cs','cs.client_id','=','c.id')
            ->where($arr)
            ->where("cs.type",Contact::TYPE_KEY)
            ->groupBy('c.id')
            ->select('c.id','c.type','c.company','c.size','c.client_rating','c.grade','u.name as principal_name');

//        $sql_with_bindings = str_replace_array('?', $clients->getBindings(), $clients->toSql());
//        dd($sql_with_bindings);
          //  ->get(['c.id','c.type','c.company','c.client_rating','c.grade','u.name as principal_name',

    }
    /**
     * @param Client $client
     * @return array
     */
    public function map($client): array
    {


        $company = $client->company;
        $grade = $client->grade == 1 ? '直客' : '代理公司';
        $principalName = $client->principal_name;
        $keyman = $client->keyman;
        $size = $this->size($client->size);
        $client_rating = $this->clientRating($client->client_rating);
        return [
            $company,
            $grade,
            $keyman,
            $size,
            $client_rating,
            $principalName

        ];
    }

    public function headings(): array
    {
        return [

            '公司名称',
            '级别',
            '决策关键人',
            '规模',
            '客户评级',
            '负责人'
        ];
    }

    /**
     * @param string $type
     * @return string $type
     */
    private function size($type)
    {
        $size = '';
        switch ($type) {
            case 1:
                $size = '上市公司';
                break;
            case 2:
                $size = '500强';
                break;

        }
        return $size;
    }
    private function clientRating($Rating)
    {
        $size = '';
        switch ($Rating) {
            case 4:
                $size = 'S';
                break;
            case 3:
                $size = 'A';
                break;
            case 2:
                $size = 'B';
                break;
            case 1:
                $size = 'C';
                break;
        }
        return $size;
    }
}
