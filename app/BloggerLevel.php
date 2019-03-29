<?php

namespace App;

abstract class BloggerLevel
{
    const S = 4;
    const A = 3;
    const B = 2;
    const C = 1;
    /**
     * @param $key
     * @return string
     */
    public static function getStr($key): string
    {
        $start = '';
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
            case BloggerLevel::C:
                $start = 'C';
                break;
        }
        return $start;
    }
}
