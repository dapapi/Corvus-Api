<?php

namespace App;

abstract class BloggerLevel
{
    const S = 1;
    const A = 2;
    const B = 3;


    /**
     * @param $key
     * @return string
     */
    public static function getStr($key): string
    {
        $start = 'S';
        switch ($key) {
            case BloggerLevel::S:
                $start = 'S';
                break;
            case BloggerLevel::A:
                $start = 'A';
                break;
            case BloggerLevel::B:
                $start = 'B';
                break;
        }
        return $start;
    }
}
