<?php

namespace App\Repositories;

use App\Models\MobileDeviceToken;
use Illuminate\Support\Facades\Log;
use UmengPusher\Umeng\Facades\Umeng;
use UmengPusher\Umeng\Pusher\UmengPusher;

class UmengRepository
{
    protected function sendMsgToAndriod($send_to,$tricker,$title,$text,$description,int $module,$data_id)
    {
        $device_tokens = (new MobileDeviceToken())->getDeviceTokens($send_to,MobileDeviceToken::ANDRIOD);
        $predefined = array('ticker' => $tricker, 'title'=>$title,'text'=>$text,'after_open'=>'com.rxsoft.papitube','description'=>$description);
        $extraField = array('module'=>$module,"data_id"=>$data_id); //other extra filed
        //单播
        Log::info($device_tokens);
        foreach ($device_tokens as $device_token){
            try{
                Log::info("向安卓[".$device_token."]发送消息");
                $res = (new UmengPusher())->android()->sendUnicast($device_token, $predefined, $extraField);
                if ($res['ret'] != "SUCCESS"){
                    Log::info("消息发送失败");
                    Log::error($res);
                }
                Log::info("消息发送成功");
                Log::info($res);
            }catch (\Exception $exception){
                Log::info("消息发送失败");
                Log::info("device_token:");
                Log::info($device_token);
                Log::info("predefined:");
                Log::info($predefined);
                Log::info("customField:");
                Log::info($extraField);
                Log::error($exception);
            }

        }
    }
    //向ios发送消息
    protected function senMsgToIos($send_to,$tricker,$title,$text,$description,int $module,$data_id)
    {
        //单播
        $device_tokens = (new MobileDeviceToken())->getDeviceTokens($send_to,MobileDeviceToken::IOS);
        $predefined = array('alert' =>['title'=>$tricker,'subtitle'=>$title,"body"=>$text],'badge'=>1,'description'=>$description);
        $customField = array('module'=>$module,"data_id"=>$data_id);
        foreach ($device_tokens as $device_token){
            try{
                Log::info("向ios:[".$device_token."]发送消息");
                $res = (new UmengPusher())->ios()->sendUnicast($device_token, $predefined, $customField);
                if ($res['ret'] != "SUCCESS"){
                    Log::info("消息发送失败");
                    Log::error($res);
                }
                Log::info("消息发送成功");
                Log::info($res);
            }catch (\Exception $e){
                Log::info("消息发送失败");
                Log::info("device_token:");
                Log::info($device_token);
                Log::info("predefined:");
                Log::info($predefined);
                Log::info("customField:");
                Log::info($customField);
                Log::error($e);
            }

        }
    }

    public function sendMsgToMobile($send_to,$tricker, $title, $text,$description,int $module,$data_id)
    {
        Log::info("消息发送");
        $this->sendMsgToAndriod($send_to,$tricker,$title,$text,$description,$module,$data_id);
        $this->senMsgToIos($send_to,$tricker,$title,$text,$description,$module,$data_id);
    }
}
