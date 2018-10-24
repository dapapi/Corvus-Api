<?php

namespace App;

use App\Models\Affix;
use App\Models\Task;
use App\Models\DepartmentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

/**
 * Class TaskPriorityStatus
 * @package App
 * 任务级别
 */
abstract class TaskPriorityStatus
{
    const HIGH = 1;
    const MIDDLE = 2;
    const LOW = 3;

}
