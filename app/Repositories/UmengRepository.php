<?php

namespace App\Repositories;

use App\Models\MobileDeviceToken;
use Illuminate\Support\Facades\Log;
use UmengPusher\Umeng\Facades\Umeng;

class UmengRepository
{
    protected function sendMsgToAndriod($send_to,$tricker,$title,$text,$description,int $module,$data_id)
    {
        $device_tokens = (new MobileDeviceToken())->getDeviceTokens($send_to,MobileDeviceToken::ANDRIOD);
        $predefined = array('ticker' => $tricker, 'title'=>$title,'text'=>$text,'after_open'=>'com.rxsoft.papitube','description'=>$description);
        $extraField = array('module'=>$module,"data_id"=>$data_id); //other extra filed
        //单播
        foreach ($device_tokens as $device_token){
            Umeng::android()->sendUnicast($device_token, $predefined, $extraField);
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
            Umeng::ios()->sendUnicast($device_token, $predefined, $customField);
        }
    }

    public function sendMsgToMobile($send_to,$tricker, $title, $text,$description,int $module,$data_id)
    {
        $this->sendMsgToAndriod($send_to,$tricker,$title,$text,$description,$module,$data_id);
        $this->senMsgToIos($send_to,$tricker,$title,$text,$description,$module,$data_id);
    }
}
