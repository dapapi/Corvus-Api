<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

/**
 * App\UserWechatInfo
 *
 * @property mixed user
 * @property mixed nickname
 * @property mixed telephone
 * @property mixed avatar
 * @property int $id
 * @property string|null $union_id
 * @property string|null $province
 * @property string|null $language
 * @property string|null $privilege
 * @property string|null $city
 * @property string|null $country
 * @property int $gender
 * @property int|null $user_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\UserWechatOpenId[] $openIds
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RegistToken[] $registTokens
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatInfo whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatInfo whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatInfo whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWechatInfo whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules/UserWechatInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules/UserWechatInfo whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules/UserWechatInfo whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules/UserWechatInfo wherePrivilege($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules/UserWechatInfo whereProvince($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules/UserWechatInfo whereUnionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules/UserWechatInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules/UserWechatInfo whereUserId($value)
 * @mixin \Eloquent
 */
class UserWechatInfo extends Model {
    const FROM_WECHAT = 1;
    const FROM_WECHAT_OPEN = 2;
    const FROM_WECHAT_APP = 3;

    protected $table = 'user_wechat_infos';
    protected $fillable = [
        'union_id',
        'nickname',
        'avatar',
        'gender',
        'province',
        'language',
        'privilege',
        'city',
        'country',
        'user_id',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function registTokens() {
        return $this->morphMany(RegistToken::class, 'tokenable');
    }

    public function openIds() {
        return $this->hasMany(UserWechatOpenId::class);
    }
}

