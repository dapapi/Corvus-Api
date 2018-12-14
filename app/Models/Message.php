<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'module',
        'title',
        'link'
    ];
    public function data()
    {
        return $this->hasMany(MessageData::class,"message_id",'id');
    }
    public function recive()
    {
        return $this->hasMany(MessageState::class,'message_id','id');
    }
}
