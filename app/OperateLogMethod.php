<?php
/**
 * Class OperateLogMethod
 * @package App
 * 操作日志方法
 */

namespace App;


abstract class OperateLogMethod
{
    const CREATE = 1;//创建
    const UPDATE = 2;//修改
    const DELETE = 3;//删除
    const FOLLOW_UP = 4;//跟进
    const LOOK = 5;//查看
}