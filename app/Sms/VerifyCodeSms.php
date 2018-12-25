<?php

namespace App\Sms;

use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Strategies\OrderStrategy;

class VerifyCodeSms extends Message {

    protected $code;
    protected $strategy = OrderStrategy::class;           // 定义本短信的网关使用策略，覆盖全局配置中的 `default.strategy`
    protected $gateways = ['qcloud']; // 定义本短信的适用平台，覆盖全局配置中的 `default.gateways`

    public function __construct($code) {
        $this->code = $code;
    }

    // 定义直接使用内容发送平台的内容
    public function getContent(GatewayInterface $gateway = null) {
        $expired_in = env('SMS_EXPIRED_IN')/60;
        return $this->code . '为您的验证码，请于' . $expired_in . '分钟内填写。如非本人操作，请忽略本短信。';
    }

    // 定义使用模板发送方式平台所需要的模板 ID
    public function getTemplate(GatewayInterface $gateway = null) {
        if ($gateway->getName() == 'qcloud') {
            return env('QCLOUD_SMS_TEMPLATE_ID');
        }
        return env('QCLOUD_SMS_TEMPLATE_ID');
    }

    // 模板参数
    public function getData(GatewayInterface $gateway = null) {
        $expired_in = env('SMS_EXPIRED_IN')/60;
        return [
            $this->code,
	        $expired_in
        ];
    }
}
