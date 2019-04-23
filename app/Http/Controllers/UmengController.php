<?php

namespace App\Http\Controllers;

use App\Repositories\UmengRepository;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class UmengController extends Controller
{
    protected $umengRepository;
    public function __construct(UmengRepository $umengRepository)
    {
        $this->umengRepository = $umengRepository;
    }
    //发送消息
    public function sendMsg()
    {
        $res = $this->umengRepository->sendMsgToAndriodTest();
        dd($res);
    }
    public function sendMsgToIos()
    {
        $res = $this->umengRepository->sendMsgToIos();
        dd($res);
    }
    //查询任务消息
    public function findTaskMesg(Request $request)
    {
        $appkey = config('umeng.android_app_key');
        $app_master_secret = config('umeng.android_app_master_secret');
        $payload = array(
            "appkey" => $appkey,
            "timestamp" =>  strval(time()),
            "task_id"   =>  $request->post('task_id')
//            "task_id"   =>  'umbwrtv155486653861000'
        );
        $payload_json = json_encode($payload);
        $url = "http://msg.umeng.com/api/status";
        $sign = md5('POST'.$url.$payload_json.$app_master_secret);
        $url = $url . "?sign=" . $sign;
        $client = new Client();
        $options = [
            "json"  =>  $payload,
//            'debug'=>true,
            'http_errors'=>false
        ];
        try{
            $response = $client->request('POST',$url,$options);
            dd(json_decode($response->getBody(),true));
        }catch (\Exception $exception){
            dd($exception->getMessage());
        }

    }
}
