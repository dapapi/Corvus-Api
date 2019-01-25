<?php

namespace App\Imports;

use App\Models\Blogger;
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

class BloggersImport implements ToCollection, WithBatchInserts, WithChunkReading
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
                $title = Blogger::where('nickname',$row[0])->get();
                if(count($title)>0){
                    throw new Exception('系统中已存在销售线索数据，请处理后再进行上传');
                }
               Blogger::create([
                    'nickname' => $row[0],
                    'platform' => $this->plat($row[1]),
                    'type_id' => $this->type($row[2]),
                    'communication_status' => $this->sign($row[3]),
                    'creator_id' => $user->id,
                    'intention' => $row[4]== '是'?'1':'0',
                    'sign_contract_other' => $row[5]== '是'?'1':'0'
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

    /**
     * @param string $type
     * @return string $type
     */
    private function sign($type)
    {
        switch ($type) {
            case '初步接触':
                $type = '1';
                break;
            case '沟通中':
                $type = '2';
                break;
            case '合同中':
                $type = '3';
                break;
            case '沟通完成':
                $type = '4';
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
            case '微博':
                $type = '1';
                break;
            case '抖音':
                $type = '2';
                break;
            case '小红书':
                $type = '3';
                break;
            case '全平台':
                $type = '4';
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
            case '搞笑剧情':
                $type = '1';
                break;
            case '美食':
                $type = '2';
                break;
            case '美妆':
                $type = '3';
                break;
            case '颜值':
                $type = '4';
                break;
            case '生活方式':
                $type = '5';
                break;
            case '生活测评':
                $type = '6';
                break;
            case '萌宠':
                $type = '7';
                break;
            case '时尚':
                $type = '8';
                break;
            case '旅行':
                $type = '9';
                break;
            case '动画':
                $type = '10';
                break;
            case '母婴':
                $type = '11';
                break;
            case '情感':
                $type = '12';
                break;
            case '摄影':
                $type = '13';
                break;
            case '舞蹈':
                $type = '14';
                break;
            case '影视':
                $type = '15';
                break;
            case '游戏':
                $type = '16';
                break;
            case '数码':
                $type = '17';
                break;
            case '街访':
                $type = '18';
                break;
            case '其他':
                $type = '19';
                break;
        }
        return $type;
    }
}
