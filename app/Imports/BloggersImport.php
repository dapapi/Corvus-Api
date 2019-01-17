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
            foreach ($rows as $key => $row) {
                if ($key == 0)
                    continue ;
                $client = Client::create([
                    'type' => $row[0],
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

    private function principal($principal_name)
    {
        $user = User::where('name', $principal_name)->first();
        if ($user)
            return $user->id;
        else
            throw new Exception('负责人不存在');
    }
}
