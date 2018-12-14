<?php
/**
 * Class CommunicationStatus
 * @package App
 * 沟通状态
 */

namespace App;


abstract class RepositoryScope
{

    // 沟通状态
    const WHOLE_MEMBERS = 1;//全体成员
    const PRESS_GROUP = 2;//宣传组
    const M11 = 3;//M11
    const RRSEARCH_AND_DEVELOPMENT = 4;//研发组



    /**
     * @param $key
     * @return string
     */
    public static function getStr($key): string
    {
        $start = '0';
        switch ($key) {
            case RepositoryScope::WHOLE_MEMBERS:
                $start = '1';
                break;
            case RepositoryScope::PRESS_GROUP:
                $start = '2';
                break;
            case RepositoryScope::M11:
                $start = '3';
                break;
            case RepositoryScope::RRSEARCH_AND_DEVELOPMENT:
                $start = '4';
                break;
        }
        return $start;
    }
}