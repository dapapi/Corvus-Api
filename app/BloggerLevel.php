<?php

namespace App;

abstract class BloggerLevel
{
    const S = 1;
    const A = 2;
    const B = 3;
    const C = 4;
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
            case BloggerLevel::C:
                $start = 'C';
                break;
        }
        return $start;
    }
}
