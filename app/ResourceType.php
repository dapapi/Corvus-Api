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
 * Class ResourceType.php
 * @package App
 * 资源类型
 */
abstract class ResourceType
{
    const BLOGGER = 1;
    const ARTIST = 2;
    const PROJECT = 3;

}
