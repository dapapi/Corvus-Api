<?php
/**
 * Class SignContractStatus
 * @package App
 * 签约状态
 */

namespace App;


abstract class SignContractStatus
{
    const UN_SIGN_CONTRACT = 1;//未签约
    const ALREADY_SIGN_CONTRACT = 2;//已签约
    const ALREADY_TERMINATE_AGREEMENT = 3;//已解约
}