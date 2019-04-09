<?php

namespace App\Repositories;

use UmengPusher\Umeng\Facades\Umeng;

class UmengRepository
{
    function sendMsgToAndriodTest()
    {
        $device_token = 'xxxx';
        $predefined = array('ticker' => '友盟消息测试', 'title'=>'消息测试标题','text'=>'消息测试内容','after_open'=>'com.rxsoft.papitube');
        $extraField = array(); //other extra filed
        Umeng::android()->sendBroadcast($predefined,$extraField); //单 播
    }
}
