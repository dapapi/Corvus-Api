<?php
/**
 * Class SignContractStatus
 * @package App
 * 签约状态
 */

namespace App;


abstract class SignContractStatus
{
    const SIGN_CONTRACTING = 1;//签约中
    const ALREADY_SIGN_CONTRACT = 2;//已签约
    const ALREADY_TERMINATE_AGREEMENT = 3;//已解约

    /**
     * @param $key
     * @return string
     */
    public static function getStr($key): string
    {
        $start = '签约中';
        switch ($key) {
            case SignContractStatus::SIGN_CONTRACTING:
                $start = '签约中';
                break;
            case SignContractStatus::ALREADY_SIGN_CONTRACT:
                $start = '已签约';
                break;
            case SignContractStatus::ALREADY_TERMINATE_AGREEMENT:
                $start = '已解约';
                break;
        }
        return $start;
    }

}