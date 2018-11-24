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
        }
        return $start;
    }

}