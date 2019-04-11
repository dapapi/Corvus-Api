<?php

namespace App\Repositories;

use UmengPusher\Umeng\Facades\Umeng;

class UmengRepository
{
    protected function sendMsgToAndriod(array $send_to,$tricker,$title,$text,int $module,$data_id)
    {
        $device_tokens = (new UserRepository)->getDeviceTokens($send_to,2);
        $predefined = array('ticker' => $tricker, 'title'=>$title,'text'=>$text,'after_open'=>'com.rxsoft.papitube');
        $extraField = array('module'=>$module,"data_id"=>$data_id); //other extra filed
        //单播
        foreach ($device_tokens as $device_token){
            Umeng::android()->sendUnicast($device_token, $predefined, $extraField);
        }
    }
    //向ios发送消息
    protected function senMsgToIos(array $send_to,$tricker,$title,$text,int $module,$data_id)
    {
        //单播
        $device_tokens = (new UserRepository)->getDeviceTokens($send_to,1);
        $predefined = array('alert' =>['title'=>$tricker,'subtitle'=>$title,"body"=>$text]);
        $customField = array('module'=>$module,"data_id"=>$data_id);
        foreach ($device_tokens as $device_token){
            Umeng::ios()->sendUnicast($device_token, $predefined, $customField);
        }
    }

    public function sendMsgToMobile(array $send_to,$tricker, $title, $text,int $module,$data_id)
    {
        $this->sendMsgToAndriod($send_to,$tricker,$title,$text,$module,$data_id);
        $this->senMsgToIos($send_to,$tricker,$title,$text,$module,$data_id);
    }
}
