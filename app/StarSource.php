<?php
/**
 * Class StarSource
 * @package App
 * 艺人来源
 */

namespace App;


abstract class StarSource
{
    const ON_LINE = 1;//线上
    const OFFLINE = 2;//线下
    const TRILL = 3;//抖音
    const WEIBO = 4;//微博
    const CHENHE = 5;//陈赫
    const BEIDIAN = 6;//北电
    const YANGGUANG = 7;//杨光
    const ZHONGXI = 8;//中戏
    const PAPITUBE = 9;//papitube推荐
    const AREA_EXTRA = 10;//地标商圈

    /**
     * @param $key
     * @return string
     */
    public static function getStr($key): string
    {
        $start = '线上';
        switch ($key) {
            case StarSource::ON_LINE:
                $start = '线上';
                break;
            case StarSource::OFFLINE:
                $start = '线下';
                break;
            case StarSource::TRILL:
                $start = '抖音';
                break;
            case StarSource::WEIBO:
                $start = '微博';
                break;
            case StarSource::CHENHE:
                $start = '陈赫';
                break;
            case StarSource::BEIDIAN:
                $start = '北电';
                break;
            case StarSource::YANGGUANG:
                $start = '杨光';
                break;
            case StarSource::ZHONGXI:
                $start = '中戏';
                break;
            case StarSource::PAPITUBE:
                $start = 'papitube推荐';
                break;
            case StarSource::AREA_EXTRA:
                $start = '地标商圈';
                break;
        }
        return $start;
    }
}