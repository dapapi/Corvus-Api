<?php

namespace App\Imports;

use App\Models\Client;
use App\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMappedCells;

class ClientsImport implements ToCollection, WithBatchInserts, WithChunkReading
{
//    public function mapping(): array
//    {
//        return [
//            'client_type' => 'A2',
//            'company' => 'B2',
//            'grade' => 'C2',
//            'principal' => 'D2',
//            'name' => 'E2',
//            'phone' => 'F2',
//            'type' => 'G2',
//            'position' => 'H2',
//            'size' => 'I2',
//        ];
//    }

    public function collection(Collection $rows)
    {
        $user = Auth::guard('api')->user();

        try {
            foreach ($rows as $key => $row){
                foreach ($rows as $key1 => $row2){

                    if($key <> $key1){
                        if($row[1] == $row2[1]){
                            throw new Exception('excel中有重复数据，请处理后再进行上传');
                        }
                    }
                }
            }
            foreach ($rows as $key => $row) {
                if ($key == 0)
                    continue ;
                $title = Client::where('company',$row[1])->get();
                if($title){
                    throw new Exception('系统中已存在销售线索数据，请处理后再进行上传');
                }
                $client = Client::create([
                    'type' => $this->type($row[0]),
                    'company' => $row[1],
                    'grade' => $row[2] == '直客' ? 1 : 2,
                    'principal_id' => $this->principal($row[3]),
                    'creator_id' => $user->id,
                    'size' => $row[8] == '上市公司' ? 2 : 1,
                ]);
                $client->contacts()->create([
                    'name' => $row[4],
                    'type' => $row[6] == '是' ? 2 : 1,
                    'phone' => $row[5],
                    'position' => $row[7],
                ]);
            }
        } catch (Exception $exception) {
            throw $exception;
        }
    }


    public function batchSize(): int
    {
        return 800;
    }

    public function chunkSize(): int
    {
        return 800;
    }

    private function type($type)
    {
        switch ($type) {
            case '影视客户':
                $type = '1';
                break;
            case '综艺客户':
                $type = '2';
                break;
            case '商务代言':
                $type = '3';
                break;
            case 'papi客户':
                $type = '4';
                break;
        }
        return $type;
    }
    private function principal($principal_name)
    {
        $user = User::where('name', $principal_name)->first();
        if ($user)
            return $user->id;
        else
            throw new Exception('负责人不存在');
    }
}
