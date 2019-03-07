<?php

namespace App;

/**
 * Class TaskPriorityStatus
 * @package App
 * 任务状态
 */
abstract class TaskStatus
{
    const NORMAL = 1;//正常
    const COMPLETE = 2;//完成
    const TERMINATION = 3;//终止
    const DELAY = 4;//延期
}
