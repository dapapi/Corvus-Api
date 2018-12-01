<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\UserWechatOpenId
 *
 * @property int $id
 * @property string $app_id
 * @property string $open_id
 * @property int $type
 * @property int|null $user_wechat_info_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\UserWechatInfo|null $userWechatInfo
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatOpenId whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatOpenId whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatOpenId whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatOpenId whereOpenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatOpenId whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatOpenId whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatOpenId whereUserWechatInfoId($value)
 * @mixin \Eloquent
 */
class UserWechatOpenId extends Model {
    const TYPE_APP = 1;
    const TYPE_OPEN = 2;
    const TYPE_SERVICE = 3;
    const TYPE_SUBSCRIPTION = 4;

    protected $table = 'user_wechat_open_ids';
    protected $fillable = [
        'open_id',
        'app_id',
        'type',
        'user_wechat_info_id'

    ];

    public function userWechatInfo() {
        return $this->belongsTo(UserWechatInfo::class);
    }
}
