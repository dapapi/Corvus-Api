<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileDeviceToken extends Model
{
    const IOS = 1;
    const ANDRIOD = 2;

    //根据userid查找device_token
    public function getDeviceTokens($user_list,$device_type)
    {
        return self::whereIn('user_id',$user_list)->where('device_type',$device_type)->pluck('device_type')->toArray();
    }
}
