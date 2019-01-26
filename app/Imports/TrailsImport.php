<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\DepartmentUser;
use App\Models\Trail;
use App\Models\Department;
use App\Models\Industry;
use App\Models\Blogger;
use App\Models\Star;
use App\User;
use Exception;
use App\Models\TrailStar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Repositories\TrailStarRepository;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TrailsImport implements ToCollection, WithBatchInserts, WithChunkReading
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|Trail
     */
//    public function model(array $row)
//    {
//        try {
//            $trail = new Trail([
//                'brand' => $row['品牌名称'],
//                'title' => $row['线索名称'],
//                'name' => $row['负责人'],
//
//            ]);
//
//            $client = ClientProtected::where('company', $row['公司名称'])->where('grade', $row['级别'])->first();
//            if (!$client) {
//                $trail->client_id = $client->id;
//            } else {
//                $client = ClientProtected::create([
//                    'company' => $row['公司名称'],
//                    'grade' => $row['级别'] == '直客' ? 1 : 2,
//                ]);
//            }
//            $user = User::where('name', $row['负责人'])->first();
//            if (!$user)
//                throw new Exception("负责人不存在");
//
//            $expectations = explode(',', $row['目标艺人']);
//            $recommandations = explode(',', $row['推荐艺人']);
//            if ($user->company == '泰洋川禾') {
//
//            }
//        } catch (Exception $exception) {
//            throw $exception;
//        }
//
//        return $trail;
//    }
    public function __construct($request)
    {
        $this->file_name = $request;
    }

    public function collection(Collection $rows)
    {
        $user = Auth::guard('api')->user();

        try {
            foreach ($rows as $key => $row){
                foreach ($rows as $key1 => $row2){

                    if($key <> $key1){
                        if($row[3] == $row2[3]){
                            throw new Exception('excel中有重复数据，请处理后再进行上传');
                        }
                    }
                }
            }
            foreach ($rows as $key => $row) {
                if ($key == 0)
                    continue ;
//                $client = ClientProtected::create([
//                    'company' =>  $row[2],
//                    'grade' => 1,
//                    'principal_id' => $this->principal($row[4],$key,'负责人'),
//                    'type' => $this->type($row[0]),
//                    'creator_id' => $user->id,
//                ]);
                $client = Client::where('company',$row[2])->first();
                if(!$client){
                    throw new Exception('请重新确认'.$key.'导入公司是否存在');
                }else {

//                    $contact = $client->contacts()->create([
//                        'client_id' => $client->id,
//                        'name' => $row[8],
//                        'phone' => $row[9],
//                    ]);
                    $contact = $client->contacts()->where('client_id',$client->id)->first();
                    if(!$contact){
                        throw new Exception('请重新确认'.$key.'导入公司是否关联的联系人');
                    }else{
                        $payload['type'] = $this->type($row[0]);
                        $payload['brand'] = $row[1];
                        $payload['title'] = $row[3];
                        $payload['resource_type'] = $this->resource($row[4]);//线索来源
                   //     $payload['resource'] = 1;
                        $industry = Industry::where('name',$row[5])->first();
                        if(!$industry){
                            throw new Exception('请重新确认'.$key.'导入行业类型');
                        }
                        $payload['industry_id'] = $industry->id;
                        $payload['principal_id'] = $this->principal($row[6], $key, '负责人');
                        $payload['contact_id'] = $contact->id;
                        $payload['client_id'] = $client->id;
                        $payload['fee'] = $row[10];
                        $payload['creator_id'] = $user->id;
                        $payload['priority'] = $this->priority($row[9]);
                        $payload['cooperation_type'] = 0;
                        $title = Trail::where('title',$row[3])->get();
                        if(count($title)>0){
                            throw new Exception('系统中已存在销售线索数据，请处理后再进行上传');
                        }
                        $trail = Trail::create($payload);
                        $expectation = $this->principals($row[7], $key, '目标艺人');
                        $recommendation = $this->principals($row[8], $key, '推荐艺人');
                        $is_expectation = $this->starStr($expectation);
                        $is_recommendation = $this->starStr($recommendation);
                        if ($row[7] && is_string($row[7]) && !empty($is_expectation)) {
                            (new TrailStarRepository())->store($trail, $is_expectation, TrailStar::EXPECTATION);
                        }
                        if ($row[8] && is_string($row[8]) && !empty($is_recommendation)) {
                            (new TrailStarRepository())->store($trail,$is_recommendation, TrailStar::RECOMMENDATION);
                        }
                   }
                }
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
    private function industry($type)
    {

        switch ($type) {
            case 'S':
                $type = 4;
                break;
            case 'A':
                $type = 3;
                break;
            case 'B':
                $type = 2;
                break;
            case 'C':
                $type = 1;
                break;
        }
        return $type;
    }
    private function priority($type)
    {

        switch ($type) {
            case 'S':
                $type = 4;
                break;
            case 'A':
                $type = 3;
                break;
            case 'B':
                $type = 2;
                break;
            case 'C':
                $type = 1;
                break;
        }
          return $type;
    }
    private function type($type)
    {
//        $user = Auth::guard('api')->user();
//        $userid = $user->id;
//        $department_ids = Department::where('department_pid', Trail::WORLDWIDE)->get(['id']);
//        $is_papi = DepartmentUser::whereIn('department_id', $department_ids)->where('user_id',$userid)->get(['user_id'])->toArray();
        $FileName = $this->file_name;
        $number = substr_count($FileName, '泰洋');
        if($number){
        switch ($type) {
            case '商务线索':
                $type = 3;
                break;
            case '影视线索':
                $type = 1;
                break;
            case '综艺线索':
                $type = 2;
                break;
          }
        }else{
            switch ($type) {
                case '商务线索':
                    $type = 4;
                    break;
                case '影视线索':
                    $type = 1;
                    break;
                case '综艺线索':
                    $type = 2;
                    break;
            }
        }
        return $type;
    }
    private function resource($type)
    {
        $FileName = $this->file_name;
        $number = substr_count($FileName, '泰洋');
        if($number) {
            switch ($type) {
                case '商务邮箱':
                    $type = 1;
                    break;
                case '工作室邮箱':
                    $type = 2;
                    break;
                case '微信公众号':
                    $type = 3;
                    break;
                case '员工':
                    $type = 4;
                    break;
                case '公司高管':
                    $type = 5;
                    break;
                case '纯中介':
                    $type = 6;
                    break;
                case '香港中介':
                    $type = 7;
                    break;
                case '台湾中介':
                    $type = 8;
                    break;
                case '复购直客':
                    $type = 9;
                    break;
                case '媒体介绍':
                    $type = 10;
                    break;
                case '公关or广告公司':
                    $type = 11;
                    break;
            }

            return $type;
        }else{
            switch ($type) {
                case '个人':
                    $type = 4;
                    break;
                case '商务邮箱/微信':
                    $type = 1;
                    break;
                case '高层推荐':
                    $type = 5;
                    break;
            }

            return $type;
       }
    }
    private function principals($stars,$key,$fild): array
    {
        $starsStr = array();
        $stars = explode(',',$stars);
        $FileName = $this->file_name;
        $number = substr_count($FileName, '泰洋');
        foreach ($stars as $star) {
            if($number)
            {
                if(empty($this->expectations($star,$key,$fild))) {
                    $starsStr[] = ['id' => '', 'flag' => 'blogger'];
                }else {
                   $starsStr[] = $this->expectations($star,$key,$fild);
                }
            }else{
                if(empty($this->expectations($star,$key,$fild))) {
                    $starsStr[] =  ['id' => '', 'flag' => 'star'];
                }else{
                    $starsStr[] = $this->expectations($star,$key,$fild);
                }

            }
        }
        $stars = $starsStr;
        return $stars;
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
    private function starStr($stars): array
    {
        $starsStr = '';
        foreach ($stars as $key => $star) {

            if($star['id'] == ''){
                unset($stars[$key]);
            }

        }
        return $stars;
    }
    private function expectations($principal_name)
    {
//        $FileName = $this->file_name;
//        $number = substr_count($FileName, '泰洋');
//        if($number)
//        {
            $user = Blogger::where('nickname', $principal_name)->first();
            if ($user) {
                return ['id' => hashid_encode($user->id), 'flag' => 'blogger'];
            }else{
                $user = Star::where('name', $principal_name)->first();
                if ($user)
                    return ['id'=>hashid_encode($user->id),'flag'=> 'star'];
                else
                    return '';
            }
//        }else{
//            $user = Star::where('name', $principal_name)->first();
//            if ($user)
//                return $user->id;
//            else
//                return '';
//        }


    }
    private function principal($principal_name,$key,$fild)
    {
        $user = User::where('name', $principal_name)->first();
        if ($user)
            return $user->id;
        else
            throw new Exception('第'.$key.'行'.$fild.'不存在');
    }
}
