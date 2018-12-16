<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Star;
use App\Models\Trail;
use App\Models\TrailStar;
use App\User;
use Exception;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TrailsImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|Trail
     */
    public function model(array $row)
    {
        try {
            $trail = new Trail([
                'brand' => $row['品牌名称'],
                'title' => $row['线索名称'],
                'name' => $row['负责人'],
                'name' => $row['目标艺人'],
                'name' => $row['推荐艺人'],
                'name' => $row['预计费用'],
                'name' => $row['线索来源'],
                'name' => $row['联系人'],
                'name' => $row['联系人电话'],
            ]);

            $client = Client::where('company', $row['公司名称'])->where('grade', $row['级别'])->first();
            if (!$client) {
                $trail->client_id = $client->id;
            } else {
                $client = Client::create([
                    'company' => $row['公司名称'],
                    'grade' => $row['级别'] == '直客' ? 1 : 2,
                ]);
            }
            $user = User::where('name', $row['负责人'])->first();
            if (!$user)
                throw new Exception("负责人不存在");

            $expectations = explode(',', $row['目标艺人']);
            $recommandations = explode(',', $row['推荐艺人']);
            if ($user->company == '泰洋川禾') {

            }
        } catch (Exception $exception) {
            throw $exception;
        }

        return $trail;
    }

    public function batchSize(): int
    {
        return 800;
    }

    public function chunkSize(): int
    {
        return 800;
    }
}
