<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\RegistToken
 *
 * @property int $id
 * @property string $token
 * @property int $expired_in
 * @property string $tokenable_type
 * @property string $tokenable_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $tokenable
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RegistToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RegistToken whereExpiredIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RegistToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RegistToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RegistToken whereTokenableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RegistToken whereTokenableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RegistToken whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RegistToken extends Model {
    protected $fillable = [
        'token',
        'expired_in',
        'tokenable_type',
        'tokenable_id'
    ];

    public function tokenable() {
        return $this->morphTo();
    }



}
