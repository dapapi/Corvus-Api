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
    const HANDLER_COMMUNICATION= 2;//经理人沟通中
    const TALENT_COMMUNICATION = 3;//兼职星探沟通中
    const UNDETERMINED = 4;//待定
    const WEED_OUT = 5;//淘汰
    const CONTRACT = 6;//合同中
    const NO_ANSWER = 7;//联系但无回复
}