<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\WechatOauthWhiteList
 *
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property int $status
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WechatOauthWhiteList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WechatOauthWhiteList whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WechatOauthWhiteList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WechatOauthWhiteList whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WechatOauthWhiteList whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WechatOauthWhiteList whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WechatOauthWhiteList extends Model {

    const STATUS_NORMAL = 1;
    const STATUS_FROZEN = 2;

    protected $table = 'wechat_oauth_whitelists';

    protected $fillable = [
        'name',
        'domain',
        'status',
    ];

}
