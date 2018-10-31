<?php
/**
 * Created by PhpStorm.
 * User: xiao
 * Date: 2018/10/25
 * Time: 上午10:18
 */

namespace App;


abstract class ProjectType
{
    const STRING = 1;
    const FLOAT = 2;
    const SELECT = 3;
    const RADIO = 4;
    const TEXT = 5;
    const DATE = 6;
    const DATETIME = 7;
    const ARTIST = 8;
    const AUTHOR = 9;
    const TASK = 10;
}