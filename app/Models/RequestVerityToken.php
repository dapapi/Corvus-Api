<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\RequestVerityToken
 *
 * @property mixed name
 * @property mixed expired_in
 * @property mixed token
 * @property int $id
 * @property string $token
 * @property string $device
 * @property string|null $telephone
 * @property string|null $sms_code
 * @property int|null $expired_in
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RequestVerityToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RequestVerityToken whereDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RequestVerityToken whereExpiredIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RequestVerityToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RequestVerityToken whereSmsCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RequestVerityToken whereTelephone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RequestVerityToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RequestVerityToken whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RequestVerityToken extends Model {
    protected $table = 'request_verity_tokens';
    protected $fillable = ['token', 'expired_in', 'device'];
}
