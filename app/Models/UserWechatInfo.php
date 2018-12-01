<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\UserWechatInfo
 *
 * @property mixed user
 * @property mixed nickname
 * @property mixed telephone
 * @property mixed avatar
 * @property int $id
 * @property string|null $union_id
 * @property string $nickname
 * @property string $avatar
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
 * @property-read \App\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo wherePrivilege($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereProvince($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereUnionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWechatInfo whereUserId($value)
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

