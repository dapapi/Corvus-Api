<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageState extends Model
{
//    use SoftDeletes;
    public function data()
    {
        return $this->hasMany(MessageData::class,"message_id",'message_id');
    }
}
