<?php
/**
 * Class CommunicationStatus
 * @package App
 * 沟通状态
 */

namespace App;


abstract class CommunicationStatus
{
    const ALREADY_SIGN_CONTRACT = 1;//已签约
    const HANDLER_COMMUNICATION = 2;//经理人沟通中
    const TALENT_COMMUNICATION = 3;//兼职星探沟通中
    const UNDETERMINED = 4;//待定
    const WEED_OUT = 5;//淘汰
    const CONTRACT = 6;//合同中
    const NO_ANSWER = 7;//联系但无回复


    /**
     * @param $key
     * @return string
     */
    public static function getStr($key): string
    {
        $start = '已签约';
        switch ($key) {
            case CommunicationStatus::ALREADY_SIGN_CONTRACT:
                $start = '已签约';
                break;
            case CommunicationStatus::HANDLER_COMMUNICATION:
                $start = '经理人沟通中';
                break;
            case CommunicationStatus::TALENT_COMMUNICATION:
                $start = '兼职星探沟通中';
                break;
            case CommunicationStatus::UNDETERMINED:
                $start = '待定';
                break;
            case CommunicationStatus::WEED_OUT:
                $start = '淘汰';
                break;
            case CommunicationStatus::CONTRACT:
                $start = '合同中';
                break;
            case CommunicationStatus::NO_ANSWER:
                $start = '联系但无回复';
                break;
        }
        return $start;
    }
}