<?php

namespace App\Repositories;

use App\Console\Commands\Project;
use App\Models\Blogger;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Star;
use App\SignContractStatus;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

/**
 * Class NcRepository
 * @package App\Repositories
 * @author lile
 * @date 2019-03-22 15:53
 */
class NcRepository
{
    public function getToken()
    {
        $cache_key = "nc:token";
        $token = Cache::get($cache_key);
        if ($token){
            return $token;
        }
        $client = new Client();
        $sysCode = config('nc.nc_syscode');//系统名称
        $sysPass = config('nc.nc_syspass');//系统密码
        $companyId = config('nc.nc_companyid');//目标系统
        $login_url = config('nc.nc_login');//登录地址
        $options = [
            "json"  =>  ["sysCode"   =>  $sysCode,"sysPass"  =>  $sysPass,"companyId"    =>$companyId],
//            "headers"   =>  ['Accept'   =>  'application/json']
        ];

        $response = $client->request('POST',$login_url,$options);
        if($response->getStatusCode() == 200){
            $body = json_decode($response->getBody(),true);
            if ($body['success'] == "true"){
                $token = $body['data']['token'];
                Cache::put($cache_key,$token,50);
                return $token;
            }
            return false;
        }
        return false;
    }

    /**
     * 发送消息到用友
     * @author lile
     * @date 2019-03-25 15:17
     */
    protected function senMessageToNC($itfconde,$data)
    {
        $token = $this->getToken();
        if (!$token){
            return false;
        }
        $options = [
            "json"  =>  ['token'=>$token,'itfcode'=>$itfconde,'companyId'=>config('nc.nc_companyid'),'data'=>$data],
            "debug" =>  true
        ];
        $client = new Client();
        $nc_query = config("nc.nc_query");
        $response = $client->request("POST",$nc_query,$options);
        if ($response->getStatusCode() == 200){
            $body = json_decode($response->getBody(),true);
            if ($body['success'] == "true"){
                return true;
            }
            throw new \Exception($response->getBody());
        }
        throw new \Exception($response->getStatusCode());
    }

    /**
     * 发送艺人消息到nc
     * @param $star
     * @author lile
     * @date 2019-03-26 14:36
     */
    public function sendStarMessageToNc(Star $star)
    {
        $itfcode = "ACTOR";
        $signflag = null;

        if($star->sign_contract_status == SignContractStatus::ALREADY_SIGN_CONTRACT){
            $signflag = 0;//签约
        }
        if($star->sign_contract_status == SignContractStatus::ALREADY_TERMINATE_AGREEMENT){
            $signflag = 1;//解约
        }
        $data = ['accode'=>$star->accode,'acname'=>$star->name,'signflag'=>$signflag,'enflag'=>$star->enflag];
        return $this->senMessageToNC($itfcode,$data);
    }
    /**
     * 发送博主消息到nc
     * @param $star
     * @author lile
     * @date 2019-03-26 14:36
     */
    public function sendBloggerMessageToNC(Blogger $blogger)
    {
        $itfcode = "ACTOR";
        $signflag = null;
        $enflag = null;
        if($blogger->sign_contract_status == SignContractStatus::ALREADY_SIGN_CONTRACT){
            $signflag = 0;//签约
        }
        if($blogger->sign_contract_status == SignContractStatus::ALREADY_TERMINATE_AGREEMENT){
            $signflag = 1;//解约
        }
        $data = ['accode'=>$blogger->accode,'acname'=>$blogger->name,'signflag'=>$blogger->$signflag,'enflag'=>$enflag];
        $this->senMessageToNC($itfcode,$data);
    }

    /**
     * 同步客户消息到nc
     * @param Client $client
     * @author lile
     * @date 2019-03-26 15:34
     */
    public function sendClientMessageToNc(Client $client)
    {
        $itfcode = "CUSTOMER";
        $data = ['cuscode' => $client->cuscode,"cusadd"=>$client->address,"cuslin"=>null,"cusphone"=>null,"cusname"=>$client->company];
        $this->senMessageToNC($itfcode,$data);
    }

    /**
     * 同步项目信息到nc
     * @param Project $project
     * @author lile
     * @date 2019-03-26 15:51
     */
    public function sendProjectMessageToNC(Project $project)
    {
        $itfcode = "PROJECT";
        $department_id  = DepartmentUser::where('user_id',$project->principal_id)->value("department_id");
        $signDept = Department::where('id',$department_id)->value('name');
        $data = ['project_code' => $project->project_code,'project_name'=>$project->title,"signDept"=>$signDept,"org"=>null,"enflag"=>null];
        $this->senMessageToNC($itfcode,$data);
    }

}
