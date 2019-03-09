<?php
/**
 * Class Whether
 * @package App
 * 是否
 */

namespace App;


abstract class Whether
{
    const NO = 0;//否
    const YES = 1;//是
    const NO2 = 2;//否 为了适应前端在艺人里加了 2表示否


    /**
     * @param $key
     * @return string
     */
    public static function getStr($key): string
    {
        $start = '是';
        switch ($key) {
            case Whether::NO:
                $start = '否';
                break;
            case Whether::YES:
                $start = '是';
                break;
            case Whether::NO2:
                $start = '否';
                break;
        }
        return $start;
    }

}