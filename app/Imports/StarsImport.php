<?php

namespace App\Imports;

use App\Models\Star;
use App\User;
use Exception;
use App\StarSource;
use App\CommunicationStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMappedCells;

class StarsImport implements ToCollection, WithBatchInserts, WithChunkReading
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
                        if($row[0] == $row2[0]){
                            throw new Exception('excel中有重复数据，请处理后再进行上传');
                        }
                    }
                }
            }
            foreach ($rows as $key => $row) {
                if ($key == 0)
                    continue ;
                $title = Star::where('name',$row[0])->get();
                if(count($title)>0){
                    throw new Exception('系统中已存在销售线索数据，请处理后再进行上传');
                }
                Star::create([
                    'name' => $row[0],
                    'gender' => $row[1] == '男'? 1 : 2,
                    'birthday' => $row[2],
                    'source' => $this->getStr($row[3]),
                    'phone' => $row[4],
                    'wechat' => $row[5],
                    'email' => $row[6],
                    'platform' => $this->platform($row[7]),
                    'artist_scout_name' => $row[8],
                    'star_location' => $row[9],
                    'communication_status' => $this->getStatus($row[10]),
                    'intention' => $row[11]== '否' ? 2: 1,
                    'sign_contract_other' => $row[12] == '否' ? 2: 1,
                    'creator_id' => $user->id,

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

    private function platform($platform)
    {
        switch ($platform) {
            case '微博':
                $platform = '1';
                break;
            case '抖音':
                $platform = '2';
                break;
            case '小红书':
                $platform = '3';
                break;
            case '全平台':
                $platform = '4';
                break;
        }
        return $platform;
    }
    private function principal($principal_name)
    {
        $user = User::where('name', $principal_name)->first();
        if ($user)
            return $user->id;
        else
            throw new Exception('负责人不存在');
    }
    public static function getStr($key): string
    {
        $start = '线上';
        switch ($key) {
            case '线上':
                $start = StarSource::ON_LINE;
                break;
            case '线下':
                $start = StarSource::OFFLINE;
                break;
            case '抖音':
                $start = StarSource::TRILL;
                break;
            case '微博':
                $start = StarSource::WEIBO;
                break;
            case '陈赫':
                $start = StarSource::CHENHE;
                break;
            case '北电':
                $start = StarSource::BEIDIAN;
                break;
            case '杨光':
                $start = StarSource::YANGGUANG;
                break;
            case '中戏':
                $start = StarSource::ZHONGXI;
                break;
            case 'papitube推荐':
                $start = StarSource::PAPITUBE;
                break;
            case '地标商圈':
                $start = StarSource::AREA_EXTRA;
                break;
        }
        return $start;
    }
    public static function getStatus($key): string
    {
        $start = '已签约';
        switch ($key) {
            case '已签约':
                $start =CommunicationStatus::ALREADY_SIGN_CONTRACT ;
                break;
            case'经理人沟通中':
                $start = CommunicationStatus::HANDLER_COMMUNICATION ;
                break;
            case '兼职星探沟通中':
                $start = CommunicationStatus::TALENT_COMMUNICATION;
                break;
            case '待定':
                $start =CommunicationStatus::UNDETERMINED ;
                break;
            case '淘汰':
                $start = CommunicationStatus::WEED_OUT;
                break;
            case '合同中':
                $start = CommunicationStatus::CONTRACT;
                break;
            case '联系但无回复':
                $start = CommunicationStatus::NO_ANSWER;
                break;
        }
        return $start;
    }
}
