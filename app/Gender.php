<?php
/**
 * Class Gender
 * @package App
 * 性别
 */

namespace App;


abstract class Gender
{
    const MAN = 1;//男
    const WOMAN = 2;//女

    /**
     * @param $key
     * @return string
     */
    public static function getStr($key): string
    {
        $start = '男';
        switch ($key) {
            case Gender::MAN:
                $start = '男';
                break;
            case Gender::WOMAN:
                $start = '女';
                break;
        }
        return $start;
    }
}