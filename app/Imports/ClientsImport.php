<?php

namespace App\Imports;

use App\Models\Client;
use App\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMappedCells;

class ClientsImport implements  ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
{
//    public function mapping(): array
//    {
//        return [
//            'client_type' => 'A2',
//            'company' => 'B2',
//            'grade' => 'C2',
//            'principal' => 'D2',
//            'name' => 'E2',
//            'type' => 'F2',
//            'phone' => 'G2',
//            'position' => 'H2',
//            'size' => 'I2',
//        ];
//    }

    public function model(array $row)
    {
        $user = Auth::guard('api')->user();
        dd($row);
        try {
            $client = Client::create([
                'type' => $row['client_type'],
                'company' => $row['company'],
                'grade' => $row['grade'] == '直客' ? 1 : 2,
                'principal_id' => $this->principal($row['principal']),
                'creator_id' => $user->id,
                'size' => $row['size'] == '上市公司' ? 2 : 1,
            ]);
            $client->contacts()->create([
                'name' => $row['name'],
                'type' => $row['type'] == '否' ? 1 : 2,
                'phone' => $row['phone'],
                'position' => $row['position'],
            ]);
        } catch (Exception $exception) {
            throw $exception;
        }

        return $client;
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
