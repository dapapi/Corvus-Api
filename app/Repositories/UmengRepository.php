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
        return Umeng::android()->sendBroadcast($predefined,$extraField); //单 播
    }

    function sendMsgToIos()
    {
        $device_token = '83805443d022102ef60a84d162d3d97a09208c13b0b075631f5cbf3a5695999d';
        $predefined = $predefined = array('alert' => ['title'=>'ios单播测试','subtitle'=>'副标题','body'=>'消息内容'] );
        $extraField = array(); //other extra filed
        return Umeng::ios()->sendUnicast($device_token,$predefined,$extraField); //单 播
    }
}
