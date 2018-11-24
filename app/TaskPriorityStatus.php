<?php

namespace App;

/**
 * Class TaskPriorityStatus
 * @package App
 * 任务级别
 */
abstract class TaskPriorityStatus
{
    const NOTHING = 0;//无
    const HIGH = 1;//高
    const MIDDLE = 2;//中
    const LOW = 3;//低

    /**
     * @param $key
     * @return string
     */
    public static function getStr($key): string
    {
        $start = '无';
        switch ($key) {
            case TaskPriorityStatus::NOTHING:
                $start = '无';
                break;
            case TaskPriorityStatus::HIGH:
                $start = '高';
                break;
            case TaskPriorityStatus::MIDDLE:
                $start = '中';
                break;
            case TaskPriorityStatus::LOW:
                $start = '低';
                break;
        }
        return $start;
    }

}
