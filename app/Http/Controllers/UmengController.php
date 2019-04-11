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
        $this->umengRepository->sendMsgToAndriodTest();
    }
    //查询任务消息
    public function findTaskMesg(Request $request)
    {
        $method = $request->method();
        $timestamp = time();
        $appkey = config('umeng.android_app_key');
        $app_master_secret = config('android_app_master_secret');
        $payload = [
            'appkey' => $appkey,
            'timestamp' =>  $timestamp,
            'task_id'   =>  $request->post('task_id')
        ];
        $payload_json = json_encode($payload);
        $url = 'http://msg.umeng.com/api/send';
        $sign = md5($method.$url.$payload_json.$app_master_secret);
        $client = new Client();
        $options = [
            "json"  =>  $payload,
        ];
        $response = $client->request('POST',$url,$options);
        dump($response->getStatusCode());
        dump($response->getBody());

    }
}
